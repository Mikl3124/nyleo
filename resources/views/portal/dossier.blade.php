@extends('layouts.portal')
@section('title', $dossier->title)
@section('content')
@php
    $company = \App\Models\CompanySetting::current();
    $pendingQuote = $dossier->quotes->first(fn ($q) => $q->isSignable());
    $plansToValidate = $dossier->documents->filter(fn ($d) => $d->isAwaitingValidation());
    $unpaid = $dossier->invoices->filter(fn ($i) => in_array($i->status->value, ['emise', 'partiellement_payee']));
    $needsInfo = in_array($dossier->status->value, ['devis_signe', 'pieces_attendues', 'plans_en_cours'])
        && (! $client->birth_date || ! $client->birth_place || ! $dossier->cadastre_number || ! $dossier->site_address);
    $keys = array_keys($steps);
    $currentIndex = array_search($current, $keys);
@endphp

<h1>{{ $dossier->title }}</h1>
<p class="muted" style="margin-top:0">{{ $dossier->procedure->label() }} — dossier {{ $dossier->reference }}@if ($dossier->siteAddress()) — {{ $dossier->siteAddress() }}@endif</p>

@if ($dossier->client_message)
    <div class="card"><strong>Message de {{ $company->displayName() }} :</strong><br>{!! nl2br(e($dossier->client_message)) !!}</div>
@endif

{{-- Ce que j'attends du client --}}
@if ($pendingQuote)
    <div class="card action">
        <h2>À faire : signer votre devis</h2>
        <p>Devis {{ $pendingQuote->number }} — <strong>{{ money($pendingQuote->total_ttc) }}</strong></p>
        <a class="btn cta" href="{{ route('portal.quotes.show', $pendingQuote) }}">Lire et signer le devis</a>
    </div>
@endif

@foreach ($plansToValidate as $plan)
    <div class="card action">
        <h2>À faire : vérifier vos plans</h2>
        <p>Les plans <strong>{{ $plan->displayName() }}</strong> sont prêts. Ouvrez-les, puis dites-moi s'ils vous conviennent.</p>
        <p><a class="btn secondary" href="{{ route('portal.documents.download', $plan) }}" target="_blank">Ouvrir les plans</a></p>
        <form method="post" action="{{ route('portal.documents.validate', $plan) }}" onsubmit="return confirm('Confirmez-vous que les plans vous conviennent ?')">
            @csrf
            <button class="btn cta block" type="submit">✓ Je valide les plans</button>
        </form>
        <details style="margin-top:14px">
            <summary>Je souhaite une modification</summary>
            <form method="post" action="{{ route('portal.documents.change', $plan) }}">
                @csrf
                <label for="feedback-{{ $plan->id }}">Quelles modifications souhaitez-vous ?</label>
                <textarea id="feedback-{{ $plan->id }}" name="feedback" rows="4" required></textarea>
                <p><button class="btn danger" type="submit">Envoyer ma demande</button></p>
            </form>
        </details>
    </div>
@endforeach

@foreach ($unpaid as $inv)
    <div class="card action">
        <h2>À régler : {{ $inv->type->label() }} {{ $inv->number }}</h2>
        <p>Montant restant : <strong>{{ money($inv->remaining()) }}</strong>@if ($inv->due_date), avant le {{ $inv->due_date->format('d/m/Y') }}@endif.</p>
        <div class="pay">
            @if ($company->iban)
                @php
                    $qr = \App\Support\PaymentQr::svg($inv, $company->snapshot());
                @endphp
                <div class="pay-transfer">
                    @if ($qr)
                        <img class="pay-qr" src="{{ $qr }}" alt="QR code de virement de {{ money($inv->remaining()) }}" width="150" height="150">
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
                        <br>Référence à indiquer : <strong>{{ $inv->number }}</strong>
                    </div>
                </div>
            @endif
            @if ($company->cheque_order)<div style="margin-top:8px"><strong>Par chèque</strong> à l'ordre de {{ $company->cheque_order }}@if ($company->cheque_address), à envoyer à : {{ $company->cheque_address }}@endif</div>@endif
        </div>
        <p><a class="btn secondary small" href="{{ route('portal.invoices.pdf', $inv) }}" target="_blank">Télécharger la facture</a></p>
    </div>
@endforeach

@if ($needsInfo)
    <div class="card action">
        <h2>À faire : compléter vos informations</h2>
        <p>Ces informations sont demandées par la mairie pour le formulaire officiel (CERFA).</p>
        <form method="post" action="{{ route('portal.dossiers.info', $dossier) }}">
            @csrf
            <div class="grid2">
                <div><label for="birth_date">Date de naissance</label><input id="birth_date" type="date" name="birth_date" value="{{ old('birth_date', $client->birth_date?->format('Y-m-d')) }}"></div>
                <div><label for="birth_place">Lieu de naissance</label><input id="birth_place" type="text" name="birth_place" value="{{ old('birth_place', $client->birth_place) }}" placeholder="Ville et département"></div>
            </div>
            <label for="site_address">Adresse du terrain</label>
            <input id="site_address" type="text" name="site_address" value="{{ old('site_address', $dossier->site_address) }}">
            <div class="grid2">
                <div><label for="site_postal_code">Code postal</label><input id="site_postal_code" type="text" name="site_postal_code" value="{{ old('site_postal_code', $dossier->site_postal_code) }}"></div>
                <div><label for="site_city">Commune</label><input id="site_city" type="text" name="site_city" value="{{ old('site_city', $dossier->site_city) }}"></div>
            </div>
            <div class="grid2">
                <div><label for="cadastre_section">Section cadastrale <span class="muted">(si connue)</span></label><input id="cadastre_section" type="text" name="cadastre_section" value="{{ old('cadastre_section', $dossier->cadastre_section) }}" placeholder="Ex. AB"></div>
                <div><label for="cadastre_number">N° de parcelle <span class="muted">(si connu)</span></label><input id="cadastre_number" type="text" name="cadastre_number" value="{{ old('cadastre_number', $dossier->cadastre_number) }}" placeholder="Ex. 756"></div>
            </div>
            <label for="phone">Téléphone</label>
            <input id="phone" type="tel" name="phone" value="{{ old('phone', $client->phone) }}">
            <label for="message">Un message, une précision sur le projet ?</label>
            <textarea id="message" name="message" rows="3">{{ old('message') }}</textarea>
            <p><button class="btn" type="submit">Enregistrer</button></p>
        </form>
    </div>
@endif

@if (in_array($dossier->status->value, ['devis_signe', 'pieces_attendues', 'plans_en_cours', 'plans_a_valider']))
    <div class="card">
        <h2>Envoyer des photos ou documents</h2>
        <p class="muted">Photos de la maison et du terrain sous plusieurs angles, plan cadastral, croquis… Vous pouvez en choisir plusieurs à la fois.</p>
        <form method="post" action="{{ route('portal.dossiers.upload', $dossier) }}" enctype="multipart/form-data">
            @csrf
            <input type="file" name="files[]" multiple accept="image/*,.pdf" required style="padding:12px 0">
            <p><button class="btn" type="submit">Envoyer mes fichiers</button></p>
        </form>
    </div>
@endif

{{-- Avancement --}}
<div class="card">
    <h2>Où en est mon dossier ?</h2>
    <ol class="steps">
        @foreach ($steps as $key => $label)
            @php $i = $loop->index; @endphp
            <li class="{{ $i < $currentIndex || ($key === 'decision' && $current === 'decision') ? 'done' : ($i === $currentIndex ? 'current' : 'todo') }}">
                <span class="dot">{{ $i < $currentIndex || ($key === 'decision' && $current === 'decision') ? '✓' : $i + 1 }}</span>
                <span>
                    {{ $label }}
                    @if ($key === 'depot' && $dossier->deposit_date)<br><span class="muted" style="font-weight:400">Déposé le {{ $dossier->deposit_date->format('d/m/Y') }}@if ($dossier->deposit_number) — n° {{ $dossier->deposit_number }}@endif</span>@endif
                    @if ($key === 'instruction' && $current === 'instruction')
                        @if ($dossier->instruction_started_at)<br><span class="muted" style="font-weight:400">Instruction commencée le {{ $dossier->instruction_started_at->format('d/m/Y') }}</span>@endif
                        @if ($dossier->incomplete_notified_at && $dossier->status->value === 'incomplet')<br><span class="muted" style="font-weight:400">La mairie a demandé des pièces le {{ $dossier->incomplete_notified_at->format('d/m/Y') }} : je prépare la réponse</span>@endif
                        @if ($dossier->incomplete_answered_at && $dossier->status->value !== 'incomplet')<br><span class="muted" style="font-weight:400">Pièces complémentaires envoyées à la mairie le {{ $dossier->incomplete_answered_at->format('d/m/Y') }}</span>@endif
                    @endif
                    @if ($key === 'instruction' && $dossier->decision_due_date && $current === 'instruction')<br><span class="muted" style="font-weight:400">Réponse de la mairie attendue au plus tard le {{ $dossier->decision_due_date->format('d/m/Y') }}</span>@endif
                    @if ($key === 'decision' && $dossier->decision_date)<br><span style="font-weight:400">{{ $dossier->status->label() }} le {{ $dossier->decision_date->format('d/m/Y') }}</span>@endif
                </span>
            </li>
        @endforeach
    </ol>
    <p class="muted" style="margin-bottom:0">Statut actuel : <strong>{{ $dossier->status->label() }}</strong></p>
</div>

{{-- Documents --}}
<div class="card">
    <h2>Mes documents</h2>
    <ul class="list">
        @foreach ($dossier->quotes as $q)
            <li><span>Devis {{ $q->number }} <span class="badge {{ $q->status->value === 'signe' ? 'ok' : '' }}">{{ $q->status->label() }}</span></span>
                <a class="btn small secondary" href="{{ route('portal.quotes.pdf', $q) }}" target="_blank">Ouvrir</a></li>
        @endforeach
        @foreach ($dossier->invoices as $inv)
            <li><span>{{ $inv->type->label() }} {{ $inv->number }} — {{ money($inv->total_ttc) }} <span class="badge {{ $inv->status->value === 'payee' ? 'ok' : 'warn' }}">{{ $inv->status->value === 'payee' ? 'Payée' : ($inv->isCreditNote() ? 'Avoir' : 'À régler') }}</span></span>
                <a class="btn small secondary" href="{{ route('portal.invoices.pdf', $inv) }}" target="_blank">Ouvrir</a></li>
        @endforeach
        @foreach ($dossier->documents as $doc)
            <li><span>{{ $doc->displayName() }} <span class="muted">— {{ $doc->category->label() }}{{ $doc->uploaded_by === 'client' ? ', envoyé par vous' : '' }}</span>
                @if ($doc->validated_at)<span class="badge ok">Validé</span>@endif</span>
                <a class="btn small secondary" href="{{ route('portal.documents.download', $doc) }}" target="_blank">Ouvrir</a></li>
        @endforeach
        @if ($dossier->quotes->isEmpty() && $dossier->invoices->isEmpty() && $dossier->documents->isEmpty())
            <li class="muted">Aucun document pour le moment.</li>
        @endif
    </ul>
</div>

@if ($dossier->events->isNotEmpty())
    <div class="card">
        <h2>Historique</h2>
        <ul class="list">
            @foreach ($dossier->events as $e)
                <li><span>{{ $e->message }}</span><span class="muted">{{ $e->created_at->format('d/m/Y') }}</span></li>
            @endforeach
        </ul>
    </div>
@endif
@endsection
