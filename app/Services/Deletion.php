<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Dossier;
use App\Models\Invoice;
use App\Models\Quote;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Suppression définitive (pas de corbeille) d'un client, d'un dossier ou d'un devis,
 * avec tout ce qui en dépend : devis, factures (via InvoiceService::delete, qui rend
 * les numéros), documents et leurs fichiers, journal, demandes de pièces, suivi e-mail.
 * Les partenaires ne sont jamais supprimés : ils perdent seulement le lien.
 */
class Deletion
{
    public static function client(Client $client): void
    {
        DB::transaction(function () use ($client) {
            foreach (self::invoicesOf(Invoice::withTrashed()->where('client_id', $client->id)) as $invoice) {
                InvoiceService::delete($invoice);
            }
            foreach (Dossier::withTrashed()->where('client_id', $client->id)->get() as $dossier) {
                self::dossier($dossier);
            }
            foreach (Quote::withTrashed()->where('client_id', $client->id)->orderByDesc('id')->get() as $quote) {
                self::quote($quote);
            }
            $client->activities()->delete();
            $client->emailMessages()->delete();
            $client->forceDelete();
        });
    }

    public static function dossier(Dossier $dossier): void
    {
        DB::transaction(function () use ($dossier) {
            foreach (self::invoicesOf(Invoice::withTrashed()->where('dossier_id', $dossier->id)) as $invoice) {
                InvoiceService::delete($invoice);
            }
            foreach (Quote::withTrashed()->where('dossier_id', $dossier->id)->orderByDesc('id')->get() as $quote) {
                self::quote($quote);
            }
            // Un par un pour que chaque fichier soit effacé du disque (événement « deleted »).
            $dossier->documents()->get()->each->delete();
            $dossier->pieceRequests()->delete();
            $dossier->events()->delete();
            $dossier->activities()->delete();
            $dossier->emailMessages()->update(['dossier_id' => null]);
            Storage::disk('local')->deleteDirectory('dossiers/'.$dossier->id);
            $dossier->forceDelete();
        });
    }

    public static function quote(Quote $quote): void
    {
        DB::transaction(function () use ($quote) {
            $number = $quote->number;
            if ($quote->signed_pdf_path) {
                Storage::disk('local')->delete($quote->signed_pdf_path);
            }
            $quote->lines()->delete();
            $quote->activities()->delete();
            $quote->emailMessages()->update(['quote_id' => null]);
            Invoice::withTrashed()->where('quote_id', $quote->id)->update(['quote_id' => null]);
            $quote->forceDelete();
            if ($number) {
                Numbering::release('quote', $number);
            }
        });
    }

    /**
     * Ce qui partira avec la sélection, pour la modale d'alerte.
     *
     * @param  iterable<Client|Dossier|Quote|Invoice>  $records
     * @return array{clients:int, dossiers:int, quotes:int, signed_quotes:int, invoices:int, issued_invoices:int, payments:int, documents:int}
     */
    public static function impact(iterable $records): array
    {
        $clientIds = $dossierIds = $quoteIds = $invoiceIdsSelected = [];
        foreach ($records as $r) {
            match (true) {
                $r instanceof Client => $clientIds[] = $r->id,
                $r instanceof Dossier => $dossierIds[] = $r->id,
                $r instanceof Quote => $quoteIds[] = $r->id,
                $r instanceof Invoice => $invoiceIdsSelected[] = $r->id,
            };
        }
        if ($clientIds) {
            $dossierIds = array_merge($dossierIds, Dossier::withTrashed()->whereIn('client_id', $clientIds)->pluck('id')->all());
        }
        $dossierIds = array_values(array_unique($dossierIds));

        $quotes = Quote::withTrashed()->where(fn ($q) => $q->whereIn('id', $quoteIds ?: [0])
            ->orWhereIn('dossier_id', $dossierIds ?: [0])->orWhereIn('client_id', $clientIds ?: [0]));
        $invoices = Invoice::withTrashed()->where(fn ($q) => $q->whereIn('dossier_id', $dossierIds ?: [0])
            ->orWhereIn('client_id', $clientIds ?: [0])->orWhereIn('id', $invoiceIdsSelected ?: [0])
            // Les avoirs partent avec leur facture.
            ->orWhereIn('parent_invoice_id', $invoiceIdsSelected ?: [0]));
        $invoiceIds = (clone $invoices)->pluck('id');

        return [
            'clients' => count($clientIds),
            'dossiers' => count($dossierIds),
            'quotes' => (clone $quotes)->count(),
            'signed_quotes' => (clone $quotes)->whereNotNull('signed_at')->count(),
            'invoices' => $invoiceIds->count(),
            'issued_invoices' => (clone $invoices)->whereNotNull('number')->count(),
            'payments' => DB::table('payments')->whereIn('invoice_id', $invoiceIds->all() ?: [0])->count(),
            'documents' => DB::table('documents')->whereIn('dossier_id', $dossierIds ?: [0])->count(),
        ];
    }

    /** Texte de la modale : liste de ce qui sera supprimé, puis les mises en garde. */
    public static function warning(iterable $records): string
    {
        $i = self::impact($records);
        $parts = array_filter([
            self::plural($i['clients'], 'client', 'clients'),
            self::plural($i['dossiers'], 'dossier', 'dossiers'),
            self::plural($i['quotes'], 'devis', 'devis'),
            self::plural($i['invoices'], 'facture ou avoir', 'factures et avoirs'),
            self::plural($i['payments'], 'paiement enregistré', 'paiements enregistrés'),
            self::plural($i['documents'], 'document (plans, photos, courriers)', 'documents (plans, photos, courriers)'),
        ]);
        $text = 'Seront supprimés définitivement : '.self::join($parts).'. Cette action est irréversible.';

        $warnings = [];
        if ($i['signed_quotes']) {
            $warnings[] = self::plural($i['signed_quotes'], 'devis signé', 'devis signés');
        }
        if ($i['issued_invoices']) {
            $warnings[] = self::plural($i['issued_invoices'], 'facture émise', 'factures émises');
        }
        if ($warnings) {
            $text .= ' Attention, cela comprend '.self::join($warnings).'. À réserver aux essais : une vraie facture émise s\'annule par un avoir.';
        }

        return $text;
    }

    /**
     * Factures de la requête, avoirs d'abord et plus récentes d'abord (les numéros reviennent
     * dans l'ordre). Chacune est rechargée au dernier moment : un avoir a pu partir avec sa facture.
     */
    private static function invoicesOf($query): \Generator
    {
        $ids = $query->orderByRaw("case when type = 'avoir' then 0 else 1 end")->orderByDesc('id')->pluck('id');
        foreach ($ids as $id) {
            if ($invoice = Invoice::withTrashed()->find($id)) {
                yield $invoice;
            }
        }
    }

    private static function plural(int $n, string $one, string $many): ?string
    {
        return $n === 0 ? null : $n.' '.($n > 1 ? $many : $one);
    }

    private static function join(array $parts): string
    {
        $parts = array_values($parts);
        if (count($parts) <= 1) {
            return $parts[0] ?? 'rien';
        }

        return implode(', ', array_slice($parts, 0, -1)).' et '.end($parts);
    }
}
