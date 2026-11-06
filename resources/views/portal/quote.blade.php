@extends('layouts.portal')
@section('title', 'Devis '.$quote->number)
@section('content')
@php($company = \App\Models\CompanySetting::current())
<p><a href="{{ $quote->dossier ? route('portal.dossiers.show', $quote->dossier) : route('portal.home') }}">← Retour à mon dossier</a></p>
<h1>Devis {{ $quote->number }}</h1>
<p class="muted" style="margin-top:0">Du {{ $quote->issue_date?->format('d/m/Y') }}@if ($quote->valid_until), valable jusqu'au {{ $quote->valid_until->format('d/m/Y') }}@endif</p>

<div class="card">
    @if ($quote->subject)<p><strong>{{ $quote->subject }}</strong></p>@endif
    <table class="q">
        @foreach ($quote->lines as $l)
            <tr><td><strong>{{ $l->label }}</strong>@if ($l->description)<br><span class="muted">{!! nl2br(e($l->description)) !!}</span>@endif
                @if ((float) $l->quantity != 1)<br><span class="muted">{{ rtrim(rtrim(number_format($l->quantity, 2, ',', ' '), '0'), ',') }} × {{ money($l->unit_price) }}</span>@endif</td>
                <td class="r">{{ money($l->total_ht) }}</td></tr>
        @endforeach
        <tr class="total"><td>Total à payer</td><td class="r">{{ money($quote->total_ttc) }}</td></tr>
    </table>
    @if (! $company->vat_enabled && $company->vat_exemption_mention)<p class="muted" style="font-size:.85rem">{{ $company->vat_exemption_mention }} : aucune TVA à ajouter.</p>@endif
    @if ((float) $quote->deposit_percent > 0)<p>Un acompte de <strong>{{ money($quote->depositAmount()) }}</strong> vous sera demandé à la signature.</p>@endif
    @if ($quote->notes)<p>{!! nl2br(e($quote->notes)) !!}</p>@endif
    <p><a class="btn small secondary" href="{{ route('portal.quotes.pdf', $quote) }}" target="_blank">Télécharger le devis (PDF)</a></p>
</div>

@if ($quote->status->value === 'signe')
    <div class="card done">
        <h2>✓ Devis signé</h2>
        <p style="margin-bottom:0">Signé par {{ $quote->signer_name }} le {{ $quote->signed_at->format('d/m/Y à H:i') }}. Merci pour votre confiance !</p>
    </div>
@elseif ($quote->isSignable())
    <div class="card action">
        <h2>Signer le devis</h2>
        <form method="post" action="{{ route('portal.quotes.sign', $quote) }}">
            @csrf
            <label for="signer_name">Votre nom et prénom</label>
            <input id="signer_name" type="text" name="signer_name" value="{{ old('signer_name', trim($client->first_name.' '.$client->last_name)) }}" required>
            <label class="check"><input type="checkbox" name="accept" value="1" required> <span>Bon pour accord : j'accepte ce devis de {{ money($quote->total_ttc) }} et ses conditions.</span></label>
            <button class="btn block" type="submit">Je signe le devis</button>
            <p class="muted" style="font-size:.85rem">Votre signature est enregistrée avec la date, l'heure et l'adresse de connexion. Une copie signée reste disponible dans votre espace.</p>
        </form>
    </div>
@else
    <div class="card"><p style="margin:0">Ce devis n'est plus signable ({{ $quote->status->label() }}). Contactez-moi pour en recevoir un nouveau.</p></div>
@endif
@endsection
