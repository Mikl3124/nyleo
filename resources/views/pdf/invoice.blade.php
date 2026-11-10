@php
    $number = $invoice->number ?: '(brouillon)';
    $title = $invoice->type->label().' '.$number;
    $isCredit = $invoice->isCreditNote();
    $issue = $invoice->issue_date ?? now();
    $dueText = null;
    if (! $isCredit) {
        $dueText = (! $invoice->due_date || $invoice->due_date->lte($issue)) ? 'À réception.' : 'Le '.$invoice->due_date->format('d/m/Y').'.';
        if (! empty($company['iban'])) {
            $dueText .= '<br><span style="font-size:8.5px">Merci d\'indiquer la référence <strong>'.e($invoice->number ?: '').'</strong> lors de votre virement.</span>';
        }
    }
    $paymentQr = $isCredit ? null : \App\Support\PaymentQr::png($invoice, $company);
    $extraRows = [];
    if ((float) $invoice->deposits_deducted > 0) {
        $extraRows[] = ['Acomptes déjà versés', '− '.money($invoice->deposits_deducted), false];
        $extraRows[] = ['Net à payer', money($invoice->amount_due), true];
    }
    if (! $isCredit && $invoice->payments->isNotEmpty()) {
        $extraRows[] = ['Déjà réglé', '− '.money($invoice->payments->sum('amount')), false];
        $extraRows[] = ['Reste à payer', money($invoice->remaining()), true];
    }
@endphp
<!DOCTYPE html>
<html lang="fr"><head><meta charset="utf-8"><title>{{ $invoice->title() }}</title>@include('pdf._styles')</head>
<body>
@include('pdf._footer')
@include('pdf._header', [
    'docTitle' => $title,
    'docDates' => array_filter(['Émise le '.$issue->format('d/m/Y'), ! $isCredit && $invoice->due_date?->gt($issue) ? 'Échéance le '.$invoice->due_date->format('d/m/Y') : null]),
])
@php($refs = array_filter([
    $isCredit && $invoice->parent ? 'Avoir se rapportant à la facture n° '.$invoice->parent->number.' du '.$invoice->parent->issue_date?->format('d/m/Y').'.' : null,
    $invoice->quote && ! $isCredit ? 'Selon devis n° '.$invoice->quote->number.($invoice->quote->signed_at ? ' signé le '.$invoice->quote->signed_at->format('d/m/Y') : '').'.' : null,
]))
@if ($refs || $invoice->subject || $invoice->dossier?->siteAddress())
    <div class="note">
        @foreach ($refs as $r){{ $r }}<br>@endforeach
        @if ($invoice->subject)<strong>Objet :</strong> {{ $invoice->subject }}<br>@endif
        @if ($invoice->dossier?->siteAddress())<strong>Lieu du projet :</strong> {{ $invoice->dossier->siteAddress() }}@endif
    </div>
@endif

@include('pdf._lines', ['lines' => $invoice->lines])

@include('pdf._bottom', ['doc' => $invoice])

@if (! $isCredit && $invoice->payments->isNotEmpty())
    <div class="paid">Paiements reçus :
        @foreach ($invoice->payments as $p) {{ money($p->amount) }} le {{ $p->paid_at->format('d/m/Y') }}@if (! $loop->last),@endif @endforeach
    </div>
@endif
@if ($invoice->notes)<div class="paid">{!! nl2br(e($invoice->notes)) !!}</div>@endif

@include('pdf._terms', [
    'footerText' => $company['invoice_footer'] ?? null,
    'extraTerms' => $isCredit ? [] : array_filter([
        $invoice->card_payment && \App\Services\CardPayment::enabled() ? 'Paiement par carte bancaire possible en ligne, depuis l\'e-mail de la facture ou votre espace client.' : null,
        ! empty($company['cheque_order']) ? "Règlement par virement ou par chèque à l'ordre de ".$company['cheque_order'].(! empty($company['cheque_address']) ? ', à envoyer à : '.$company['cheque_address'] : '').'.' : null,
        $company['late_penalty_text'] ?? null,
    ]),
])
</body></html>
