<!DOCTYPE html>
<html lang="fr"><head><meta charset="utf-8"><title>Devis {{ $quote->number }}</title>@include('pdf._styles')</head>
<body>
@include('pdf._footer')
@include('pdf._header')

<p class="doc-title">Devis {{ $quote->number ?: '(brouillon)' }}</p>
<p class="muted">
    Date : {{ ($quote->issue_date ?? now())->format('d/m/Y') }}
    @if ($quote->valid_until) — Valable jusqu'au {{ $quote->valid_until->format('d/m/Y') }}@endif
    @if ($quote->dossier) — Dossier {{ $quote->dossier->reference }}@endif
</p>
@if ($quote->subject)<p><strong>Objet :</strong> {{ $quote->subject }}</p>@endif
@if ($quote->dossier?->siteAddress())<p><strong>Lieu du projet :</strong> {{ $quote->dossier->siteAddress() }}</p>@endif

@php($extra = (float) $quote->deposit_percent > 0 ? '<tr><td>Acompte à la signature ('.rtrim(rtrim(number_format($quote->deposit_percent, 2, ',', ''), '0'), ',').' %)</td><td class="r">'.money($quote->depositAmount()).'</td></tr>' : '')
@include('pdf._lines', ['lines' => $quote->lines, 'doc' => $quote, 'extraTotals' => $extra])

@if ($quote->notes)
    <div class="section"><strong>Conditions particulières</strong><br>{!! nl2br(e($quote->notes)) !!}</div>
@endif

<div class="section small">
    @if (! empty($company['payment_terms_text']))<strong>Conditions de règlement :</strong> {{ $company['payment_terms_text'] }}<br>@endif
    Les taxes d'urbanisme (taxe d'aménagement, redevance d'archéologie préventive) éventuellement dues à l'administration ne sont pas comprises.
    @if (! empty($company['quote_footer']))<br>{!! nl2br(e($company['quote_footer'])) !!}@endif
</div>

<div class="sign">
    @if ($quote->signed_at)
        <span class="stamp">BON POUR ACCORD — SIGNÉ ÉLECTRONIQUEMENT</span><br>
        Par : <strong>{{ $quote->signer_name }}</strong><br>
        Le {{ $quote->signed_at->timezone('Europe/Paris')->format('d/m/Y à H:i:s') }}@if ($quote->signer_ip) depuis l'adresse IP {{ $quote->signer_ip }}@endif<br>
        <span class="small muted">Empreinte de signature (SHA-256) : {{ $quote->signature_hash }}</span>
    @else
        <strong>Bon pour accord</strong> — date et signature du client :<br><br><br>
        <span class="small muted">Vous pouvez aussi signer ce devis en ligne depuis votre espace client.</span>
    @endif
</div>
</body></html>
