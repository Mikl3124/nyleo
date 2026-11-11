@extends('layouts.portal')
@section('title', $invoice->type->label().' '.$invoice->number)
@section('content')
@php
    $company = \App\Models\CompanySetting::current();
    $isCredit = $invoice->isCreditNote();
    $toPay = ! $isCredit && in_array($invoice->status->value, ['emise', 'partiellement_payee'], true) && $invoice->remaining() > 0;
    $cardUrl = $toPay ? \App\Services\CardPayment::url($invoice) : null;
    $qr = $toPay && $company->iban ? \App\Support\PaymentQr::svg($invoice, $company->snapshot()) : null;
@endphp
<p><a href="{{ $invoice->dossier ? route('portal.dossiers.show', $invoice->dossier) : route('portal.home') }}">← Retour à mon dossier</a></p>
<h1>{{ $invoice->type->label() }} {{ $invoice->number }}</h1>
<p class="muted" style="margin-top:0">Du {{ $invoice->issue_date?->format('d/m/Y') }}@if ($toPay && $invoice->due_date), à régler avant le {{ $invoice->due_date->format('d/m/Y') }}@endif</p>

@if ($toPay)
    {{-- Le paiement d'abord : c'est ce que le client vient faire ici --}}
    <section class="card action" id="payer">
        <h2>Payer ma facture</h2>
        <p>Montant à régler : <strong>{{ money($invoice->remaining()) }}</strong></p>
        <div class="pay">
            @if ($cardUrl)
                <div class="pay-card">
                    <p><a class="btn cta block" href="{{ $cardUrl }}">Payer ma facture par carte bancaire</a></p>
                    <p class="muted">Paiement sécurisé par Stripe.@if ($company->iban) Vous pouvez aussi payer par virement, ci-dessous.@endif</p>
                </div>
            @endif
            @if ($company->iban)
                <div class="pay-transfer" @if ($cardUrl) style="margin-top:14px;padding-top:14px;border-top:1px solid var(--line)" @endif>
                    @if ($qr)
                        <img class="pay-qr" src="{{ $qr }}" alt="QR code de virement de {{ money($invoice->remaining()) }}" width="150" height="150">
                    @endif
                    <div>
                        <strong>Par virement</strong>
                        @if ($qr)
                            <br>Scannez avec l'application de votre banque : le montant, l'IBAN et la référence sont déjà remplis.
                        @endif
                        <br>IBAN : <strong>{{ \App\Support\PaymentQr::formatIban($company->iban) }}</strong>
                        @if ($company->bic)
                            <br>BIC : {{ $company->bic }}
                        @endif
                        <br>Référence à indiquer : <strong>{{ $invoice->number }}</strong>
                    </div>
                </div>
            @endif
        </div>
    </section>
@elseif (! $isCredit && $invoice->status->value === 'payee')
    <div class="card done">
        <h2>✓ Facture réglée</h2>
        <p style="margin:0">Merci, votre règlement est bien reçu.</p>
    </div>
@endif

<div class="card">
    @if ($invoice->subject)<p><strong>{{ $invoice->subject }}</strong></p>@endif
    <table class="q">
        @foreach ($invoice->lines as $l)
            <tr><td><strong>{{ $l->label }}</strong>@if ($l->description)<br><span class="muted">{!! nl2br(e($l->description)) !!}</span>@endif
                @if ((float) $l->quantity != 1)<br><span class="muted">{{ rtrim(rtrim(number_format($l->quantity, 2, ',', ' '), '0'), ',') }} × {{ money($l->unit_price) }}</span>@endif</td>
                <td class="r">{{ money($l->total_ht) }}</td></tr>
        @endforeach
        @if ((float) $invoice->deposits_deducted > 0)
            <tr><td>Acomptes déjà facturés</td><td class="r">− {{ money($invoice->deposits_deducted) }}</td></tr>
        @endif
        @if ((float) $invoice->amount_paid > 0 && ! $isCredit)
            <tr><td>Déjà réglé</td><td class="r">− {{ money($invoice->amount_paid) }}</td></tr>
        @endif
        <tr class="total"><td>{{ $isCredit ? 'Montant de l\'avoir' : ($toPay ? 'Reste à payer' : 'Total') }}</td><td class="r">{{ money($toPay ? $invoice->remaining() : ($isCredit ? $invoice->total_ttc : $invoice->amount_due)) }}</td></tr>
    </table>
    @if (! $company->vat_enabled && $company->vat_exemption_mention)<p class="muted" style="font-size:.85rem">{{ $company->vat_exemption_mention }} : aucune TVA à ajouter.</p>@endif
    @if ($invoice->notes)<p>{!! nl2br(e($invoice->notes)) !!}</p>@endif
    <p><a class="btn small secondary" href="{{ route('portal.invoices.pdf', $invoice) }}" target="_blank">Télécharger {{ $isCredit ? 'l\'avoir' : 'la facture' }} (PDF)</a></p>
</div>
@endsection
