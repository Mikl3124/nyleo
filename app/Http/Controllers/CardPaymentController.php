<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\CardPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/** Paiement d'une facture par carte : départ vers Stripe, retour (merci / annulé) et webhook. */
class CardPaymentController extends Controller
{
    /** Lien signé « Payer par carte » : ouvre la page de paiement Stripe. */
    public function start(Invoice $invoice)
    {
        if (! CardPayment::available($invoice)) {
            return $this->page($invoice, $invoice->remaining() <= 0 || $invoice->status->value === 'payee' ? 'paid' : 'unavailable');
        }

        try {
            return redirect()->away(CardPayment::checkoutUrl($invoice));
        } catch (RuntimeException) {
            return $this->page($invoice, 'error');
        }
    }

    /** Retour de Stripe après paiement : le paiement est enregistré tout de suite (le webhook ne fera pas de doublon). */
    public function success(Request $request, Invoice $invoice)
    {
        abort_unless($request->hasValidSignatureWhileIgnoring(['session_id', 'copie']), 403);
        $session = CardPayment::fetchSession((string) $request->query('session_id'));
        if ($session && (int) ($session['metadata']['invoice_id'] ?? 0) === $invoice->id) {
            CardPayment::record($session);
            if (($session['payment_status'] ?? null) === 'paid') {
                return $this->page($invoice->refresh(), 'success', round(($session['amount_total'] ?? 0) / 100, 2));
            }
        }

        return $this->page($invoice, 'pending');
    }

    public function cancel(Invoice $invoice)
    {
        return $this->page($invoice, 'cancelled');
    }

    public function webhook(Request $request)
    {
        $payload = $request->getContent();
        if (! CardPayment::validSignature($payload, $request->header('Stripe-Signature'))) {
            Log::warning('Stripe : webhook refusé (signature invalide)');

            return response('Signature invalide', 400);
        }

        $event = json_decode($payload, true) ?: [];
        if (in_array($event['type'] ?? null, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)) {
            CardPayment::record($event['data']['object'] ?? []);
        }

        return response('ok');
    }

    private function page(Invoice $invoice, string $state, ?float $amount = null)
    {
        abort_if(! $invoice->number || $invoice->isCreditNote(), 404);

        return response()->view('payment.card', [
            'invoice' => $invoice,
            'state' => $state,
            'amount' => $amount,
            'retryUrl' => CardPayment::url($invoice),
            'portalUrl' => route('portal.home'),
        ]);
    }
}
