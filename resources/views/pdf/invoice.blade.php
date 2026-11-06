<!DOCTYPE html>
<html lang="fr"><head><meta charset="utf-8"><title>{{ $invoice->title() }}</title>@include('pdf._styles')</head>
<body>
@include('pdf._footer')
@include('pdf._header')

<p class="doc-title">{{ $invoice->type->label() }} {{ $invoice->number ?: '(brouillon)' }}</p>
<p class="muted">
    Date d'émission : {{ ($invoice->issue_date ?? now())->format('d/m/Y') }}
    @if (! $invoice->isCreditNote() && $invoice->due_date) — Échéance : {{ $invoice->due_date->format('d/m/Y') }}@endif
    @if ($invoice->dossier) — Dossier {{ $invoice->dossier->reference }}@endif
</p>
@if ($invoice->isCreditNote() && $invoice->parent)
    <p><strong>Avoir se rapportant à la facture n° {{ $invoice->parent->number }} du {{ $invoice->parent->issue_date?->format('d/m/Y') }}.</strong></p>
@endif
@if ($invoice->quote && ! $invoice->isCreditNote())<p class="muted">Selon devis n° {{ $invoice->quote->number }} signé le {{ $invoice->quote->signed_at?->format('d/m/Y') }}.</p>@endif
@if ($invoice->subject)<p><strong>Objet :</strong> {{ $invoice->subject }}</p>@endif

@php($extra = '')
@if ((float) $invoice->deposits_deducted > 0)
    @php($extra = '<tr><td>Acomptes déjà versés</td><td class="r">− '.money($invoice->deposits_deducted).'</td></tr><tr class="grand"><td>Net à payer</td><td class="r">'.money($invoice->amount_due).'</td></tr>')
@endif
@include('pdf._lines', ['lines' => $invoice->lines, 'doc' => $invoice, 'extraTotals' => $extra])

@if ($invoice->notes)<div class="section">{!! nl2br(e($invoice->notes)) !!}</div>@endif

@unless ($invoice->isCreditNote())
    <div class="section box">
        <strong>Règlement</strong><br>
        @if (! empty($company['iban']))
            Par virement : IBAN <strong>{{ $company['iban'] }}</strong>@if (! empty($company['bic'])) — BIC {{ $company['bic'] }}@endif @if (! empty($company['bank_name']))({{ $company['bank_name'] }})@endif<br>
            Merci d'indiquer la référence <strong>{{ $invoice->number }}</strong>.<br>
        @endif
        @if (! empty($company['cheque_order']))
            Par chèque à l'ordre de <strong>{{ $company['cheque_order'] }}</strong>@if (! empty($company['cheque_address'])), à envoyer à : {{ $company['cheque_address'] }}@endif.<br>
        @endif
        @if ($invoice->payments->isNotEmpty())
            <br>Paiements reçus :
            @foreach ($invoice->payments as $p) {{ money($p->amount) }} le {{ $p->paid_at->format('d/m/Y') }}@if (! $loop->last),@endif @endforeach
            — Reste à payer : <strong>{{ money($invoice->remaining()) }}</strong>
        @endif
    </div>
    <div class="section small muted">
        @if (! empty($company['payment_terms_text'])){{ $company['payment_terms_text'] }}<br>@endif
        @if (! empty($company['late_penalty_text'])){{ $company['late_penalty_text'] }}<br>@endif
        @if (! empty($company['invoice_footer'])){!! nl2br(e($company['invoice_footer'])) !!}@endif
    </div>
@endunless
</body></html>
