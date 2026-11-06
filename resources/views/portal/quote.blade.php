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
        @if ($quote->early_start_requested)<p class="muted" style="margin:8px 0 0">Vous avez demandé que le travail commence avant la fin du délai de rétractation.</p>@endif
    </div>
@elseif ($quote->isSignable())
    <style>
        .why{margin:12px 0 4px;background:#fffdf8;border:1px solid var(--line);border-radius:12px;padding:10px 14px}
        .why summary{cursor:pointer;font-weight:700;color:var(--teal-d);list-style:none;display:flex;align-items:center;gap:10px}
        .why summary::-webkit-details-marker{display:none}
        .why summary span{flex:none;width:26px;height:26px;border-radius:50%;background:var(--teal-d);color:#fff;font-family:Georgia,serif;font-style:italic;font-weight:700;display:flex;align-items:center;justify-content:center}
        .why p{margin:10px 0 4px;font-size:.92rem}
    </style>
    <div class="card action">
        <h2>Signer le devis</h2>
        <form method="post" action="{{ route('portal.quotes.sign', $quote) }}">
            @csrf
            <label for="signer_name">Votre nom et prénom</label>
            <input id="signer_name" type="text" name="signer_name" value="{{ old('signer_name', trim($client->first_name.' '.$client->last_name)) }}" required>
            <div class="grid2">
                <div><label for="birth_date">Date de naissance</label><input id="birth_date" type="date" name="birth_date" value="{{ old('birth_date', $client->birth_date?->format('Y-m-d')) }}" max="{{ now()->subDay()->format('Y-m-d') }}" required></div>
                <div><label for="birth_place">Lieu de naissance</label><input id="birth_place" type="text" name="birth_place" value="{{ old('birth_place', $client->birth_place) }}" placeholder="Ville et département" required></div>
            </div>
            <details class="why">
                <summary><span aria-hidden="true">i</span> Pourquoi je vous demande ces informations ?</summary>
                <p>La mairie exige votre date et votre lieu de naissance sur le formulaire officiel (CERFA) du permis de construire ou de la déclaration préalable. En les donnant maintenant, vous n'aurez pas à me les renvoyer plus tard.</p>
                <p>Elles servent uniquement à préparer votre dossier pour la mairie et ne sont transmises à personne d'autre. Vous pouvez à tout moment me demander de les consulter, de les corriger ou de les supprimer (RGPD).</p>
            </details>
            <label class="check"><input type="checkbox" name="accept" value="1" required> <span>Bon pour accord : j'accepte ce devis de {{ money($quote->total_ttc) }} et les <a href="{{ $company->cgvUrl() }}" target="_blank">conditions générales de vente</a>.</span></label>
            <label class="check"><input type="checkbox" name="early_start" value="1" @checked(old('early_start'))> <span>Je demande que le travail commence tout de suite, sans attendre la fin de mon délai de rétractation de 14 jours. Si je me rétracte ensuite, je paierai le travail déjà fait. Une fois les plans livrés, je ne pourrai plus me rétracter.</span></label>
            <p class="muted" style="font-size:.85rem;margin-top:-4px">Case facultative. Si vous ne la cochez pas, le travail commence après les 14 jours.</p>
            <button class="btn cta block" type="submit">Je signe le devis</button>
            <p class="muted" style="font-size:.85rem">Votre signature est enregistrée avec la date, l'heure et l'adresse de connexion. Une copie signée reste disponible dans votre espace.</p>
        </form>
    </div>
@else
    <div class="card"><p style="margin:0">Ce devis n'est plus signable ({{ $quote->status->label() }}). Contactez-moi pour en recevoir un nouveau.</p></div>
@endif
@endsection
