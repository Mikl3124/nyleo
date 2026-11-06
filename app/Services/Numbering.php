<?php

namespace App\Services;

use App\Enums\InvoiceType;
use App\Models\CompanySetting;
use App\Models\Invoice;
use App\Models\Quote;
use Illuminate\Support\Facades\DB;

/**
 * Numérotation chronologique et continue, sans trou (art. 242 nonies A, annexe II du CGI).
 * Format conservé de l'existant : D202611-134, F202611-117, AV202611-3.
 */
class Numbering
{
    /** Numéro que recevra le prochain document, sans le réserver (aperçu avant envoi). */
    public static function peek(string $kind): string
    {
        $settings = CompanySetting::current();
        [$prefixField, $counterField] = self::fields($kind);

        return $settings->{$prefixField}.now()->format('Ym').'-'.max(1, (int) $settings->{$counterField});
    }

    private static function fields(string $kind): array
    {
        return match ($kind) {
            'quote' => ['quote_prefix', 'quote_next_number'],
            'invoice' => ['invoice_prefix', 'invoice_next_number'],
            'credit_note' => ['credit_note_prefix', 'credit_note_next_number'],
        };
    }

    public static function next(string $kind): string
    {
        return DB::transaction(function () use ($kind) {
            $settings = CompanySetting::query()->lockForUpdate()->first() ?? CompanySetting::current();
            [$prefixField, $counterField] = self::fields($kind);
            $seq = max(1, (int) $settings->{$counterField});
            $settings->forceFill([$counterField => $seq + 1])->save();

            return $settings->{$prefixField}.now()->format('Ym').'-'.$seq;
        });
    }

    /**
     * Rend un numéro qui vient d'être supprimé : si c'était le dernier attribué,
     * le compteur recule d'un cran. Sinon on ne touche à rien (pas de doublon possible).
     */
    public static function release(string $kind, string $number): void
    {
        $counterField = self::counterField($kind);
        if (! preg_match('/-(\d+)$/', $number, $m)) {
            return;
        }
        DB::transaction(function () use ($counterField, $m) {
            $settings = CompanySetting::query()->lockForUpdate()->first();
            if ($settings && (int) $settings->{$counterField} === (int) $m[1] + 1) {
                $settings->forceFill([$counterField => (int) $m[1]])->save();
            }
        });
    }

    /** Prochain numéro d'après les pièces existantes : 1 s'il n'y en a aucune. */
    public static function nextFromExisting(string $kind): int
    {
        $query = match ($kind) {
            'quote' => Quote::withTrashed(),
            'invoice' => Invoice::withTrashed()->where('type', '!=', InvoiceType::Avoir->value),
            'credit_note' => Invoice::withTrashed()->where('type', InvoiceType::Avoir->value),
        };
        $max = 0;
        foreach ($query->whereNotNull('number')->pluck('number') as $number) {
            if (preg_match('/-(\d+)$/', $number, $m)) {
                $max = max($max, (int) $m[1]);
            }
        }

        return $max + 1;
    }

    public static function counterField(string $kind): string
    {
        return match ($kind) {
            'quote' => 'quote_next_number',
            'invoice' => 'invoice_next_number',
            'credit_note' => 'credit_note_next_number',
        };
    }
}
