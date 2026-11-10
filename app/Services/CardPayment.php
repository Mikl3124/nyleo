<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Notifications\AdminAlert;
use App\Support\Admin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use RuntimeException;

/**
 * Paiement d'une facture par carte bancaire avec Stripe Checkout (page de paiement hébergée par Stripe).
 * Activé par facture (case « Paiement par carte » de l'admin) et seulement si STRIPE_SECRET est renseignée.
 * Le paiement est enregistré au retour du client (page « merci ») et par le webhook, sans doublon.
 */
class CardPayment
{
    private const API = 'https://api.stripe.com/v1';

    public static function enabled(): bool
    {
        return filled(config('services.stripe.secret'));
    }

    /** La facture peut-elle être réglée par carte maintenant ? */
    public static function available(Invoice $invoice): bool
    {
        return self::enabled()
            && $invoice->card_payment
            && $invoice->number
            && ! $invoice->isCreditNote()
            && in_array($invoice->status, [InvoiceStatus::Emise, InvoiceStatus::PartiellementPayee], true)
            && $invoice->remaining() >= 0.5; // minimum Stripe : 0,50 €
    }

    /** Lien « Payer par carte » (e-mail, espace client) : signé, sans connexion, valable 120 jours. */
    public static function url(Invoice $invoice): ?string
    {
        return self::available($invoice)
            ? URL::temporarySignedRoute('payment.card', now()->addDays(120), ['invoice' => $invoice->id])
            : null;
    }

    /** Crée la session de paiement Stripe pour le reste à régler et renvoie son adresse. */
    public static function checkoutUrl(Invoice $invoice): string
    {
        if (! self::available($invoice)) {
            throw new RuntimeException('Le paiement par carte n\'est pas disponible pour cette facture.');
        }

        $label = $invoice->type->label().' '.$invoice->number;
        $params = [
            'mode' => 'payment',
            'locale' => 'fr',
            'payment_method_types' => ['card'],
            'client_reference_id' => (string) $invoice->id,
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => (int) round($invoice->remaining() * 100),
                    'product_data' => ['name' => $label, 'description' => trim(($invoice->subject ?: '').' — '.($invoice->client_snapshot['display_name'] ?? $invoice->client?->full_name), ' —')],
                ],
            ]],
            'metadata' => ['invoice_id' => $invoice->id, 'invoice_number' => $invoice->number],
            'payment_intent_data' => [
                'description' => $label,
                'metadata' => ['invoice_id' => $invoice->id, 'invoice_number' => $invoice->number],
            ],
            // Liens signés : Stripe ajoute session_id, ignoré par la vérification de la signature.
            'success_url' => URL::temporarySignedRoute('payment.card.success', now()->addDays(2), ['invoice' => $invoice->id]).'&session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => URL::temporarySignedRoute('payment.card.cancel', now()->addDays(2), ['invoice' => $invoice->id]),
        ];
        if (filter_var($invoice->client?->email, FILTER_VALIDATE_EMAIL)) {
            $params['customer_email'] = $invoice->client->email;
        }

        $response = Http::withToken(config('services.stripe.secret'))->asForm()->timeout(20)
            ->post(self::API.'/checkout/sessions', $params);
        if (! $response->successful() || ! $response->json('url')) {
            Log::error('Stripe : création du paiement impossible', ['invoice' => $invoice->id, 'status' => $response->status(), 'error' => $response->json('error.message')]);
            throw new RuntimeException('Le paiement par carte est momentanément indisponible.');
        }

        return $response->json('url');
    }

    public static function fetchSession(string $sessionId): ?array
    {
        if (! self::enabled() || ! preg_match('/^cs_[A-Za-z0-9_]+$/', $sessionId)) {
            return null;
        }
        $response = Http::withToken(config('services.stripe.secret'))->timeout(20)->get(self::API.'/checkout/sessions/'.$sessionId);

        return $response->successful() ? $response->json() : null;
    }

    /** Enregistre le paiement d'une session Stripe réglée. Sans effet si elle est déjà enregistrée. */
    public static function record(array $session): ?Payment
    {
        $invoiceId = (int) ($session['metadata']['invoice_id'] ?? $session['client_reference_id'] ?? 0);
        if (($session['payment_status'] ?? null) !== 'paid' || ! $invoiceId || ($session['currency'] ?? 'eur') !== 'eur') {
            return null;
        }
        $reference = (string) ($session['payment_intent'] ?? $session['id'] ?? '');
        $amount = round(((int) ($session['amount_total'] ?? 0)) / 100, 2);
        if ($reference === '' || $amount <= 0) {
            return null;
        }

        $payment = DB::transaction(function () use ($invoiceId, $reference, $amount) {
            $invoice = Invoice::whereKey($invoiceId)->lockForUpdate()->first();
            if (! $invoice || $invoice->payments()->where('reference', $reference)->exists()) {
                return null;
            }

            return InvoiceService::addPayment($invoice, $amount, 'carte', now(), $reference);
        });

        if ($payment) {
            $invoice = $payment->invoice;
            Admin::notify(new AdminAlert(
                "Facture {$invoice->number} payée par carte",
                ($invoice->client_snapshot['display_name'] ?? $invoice->client?->full_name).' a réglé '.money($amount).' par carte bancaire.',
                route('filament.admin.resources.invoices.edit', $invoice),
            ));
        }

        return $payment;
    }

    /** Vérifie la signature d'un webhook Stripe (en-tête Stripe-Signature, tolérance 5 minutes). */
    public static function validSignature(string $payload, ?string $header): bool
    {
        $secret = (string) config('services.stripe.webhook_secret');
        if ($secret === '' || ! $header) {
            return false;
        }
        $timestamp = null;
        $signatures = [];
        foreach (explode(',', $header) as $part) {
            [$k, $v] = array_pad(explode('=', trim($part), 2), 2, '');
            if ($k === 't') {
                $timestamp = (int) $v;
            } elseif ($k === 'v1') {
                $signatures[] = $v;
            }
        }
        if (! $timestamp || abs(time() - $timestamp) > 300) {
            return false;
        }
        $expected = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);
        foreach ($signatures as $sig) {
            if (hash_equals($expected, $sig)) {
                return true;
            }
        }

        return false;
    }
}
