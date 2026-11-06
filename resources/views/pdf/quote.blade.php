@php
    $number = $quote->number ?: '(brouillon)';
    $days = (int) ($company['payment_terms_days'] ?? 0);
    $dueText = $days > 0 ? 'À '.$days.' jours après réception de la facture.' : 'À réception.';
    $extraRows = (float) $quote->deposit_percent > 0
        ? [['Acompte à la signature ('.pdf_number($quote->deposit_percent).' %)', money($quote->depositAmount()), false]]
        : [];
@endphp
<!DOCTYPE html>
<html lang="fr"><head><meta charset="utf-8"><title>Devis {{ $number }}</title>@include('pdf._styles')</head>
<body>
@include('pdf._footer')
@include('pdf._header', [
    'docTitle' => 'Devis '.$number,
    'docDates' => array_filter(['Émis le '.($quote->issue_date ?? now())->format('d/m/Y'), $quote->valid_until ? "Valide jusqu'au ".$quote->valid_until->format('d/m/Y') : null]),
])
@if ($quote->subject || $quote->dossier?->siteAddress())
    <div class="note">
        @if ($quote->subject)<strong>Objet :</strong> {{ $quote->subject }}<br>@endif
        @if ($quote->dossier?->siteAddress())<strong>Lieu du projet :</strong> {{ $quote->dossier->siteAddress() }}@endif
    </div>
@endif

@include('pdf._lines', ['lines' => $quote->lines])

@include('pdf._bottom', ['doc' => $quote])

@if ($quote->notes)
    <div class="paid"><strong>Conditions particulières</strong><br>{!! nl2br(e($quote->notes)) !!}</div>
@endif

<div class="sign">
    <strong>Bon pour accord</strong><br>
    @if ($quote->signed_at)
        Signé le : {{ $quote->signed_at->timezone('Europe/Paris')->format('d/m/Y à H:i') }}<br>
        Par : <strong>{{ $quote->signer_name }}</strong>
        <div class="small">Signature électronique{{ $quote->signer_ip ? " depuis l'adresse IP ".$quote->signer_ip : '' }}<br>Empreinte SHA-256 : {{ \Illuminate\Support\Str::limit($quote->signature_hash, 32, '…') }}</div>
        @if ($quote->terms_accepted_at)<div class="small">Le client a accepté les conditions générales de vente et a autorisé {{ ($company['trade_name'] ?? null) ?: (($company['company_name'] ?? null) ?: 'Nyleo') }} à déposer la demande en son nom et à en assurer le suivi jusqu'à la décision (le {{ $quote->terms_accepted_at->timezone('Europe/Paris')->format('d/m/Y à H:i') }}).</div>@endif
        @if ($quote->early_start_requested)<div class="small">Le client a demandé que la prestation commence avant la fin du délai de rétractation de 14 jours (article L221-25 du Code de la consommation).</div>@endif
    @else
        Signé le :
    @endif
</div>

@include('pdf._terms', ['footerText' => $company['quote_footer'] ?? null])
</body></html>
