@extends('layouts.portal')
@section('title', $dossier->title)
@section('content')
@php
    $company = \App\Models\CompanySetting::current();
    $pendingQuote = $dossier->quotes->first(fn ($q) => $q->isSignable());
    $plansToValidate = $dossier->documents->filter(fn ($d) => $d->isAwaitingValidation());
    $unpaid = $dossier->invoices->filter(fn ($i) => in_array($i->status->value, ['emise', 'partiellement_payee']));
    // L'envoi de photos est facultatif : jamais une étape, jamais bloquant, possible tant que le dossier est ouvert.
    $canUpload = ! in_array($dossier->status->value, ['accorde', 'refuse', 'abandonne']);
    // Une seule action ocre par écran : la première chose à faire.
    // Demande de pièces de la mairie qui attend des documents du client.
    $mairieRequests = $dossier->pieceRequests->filter(fn ($pr) => $pr->isOpen() && $pr->waiting_on?->value === 'client');
    $cta = $pendingQuote ? 'quote' : ($plansToValidate->isNotEmpty() ? 'plans' : ($unpaid->isNotEmpty() ? 'pay' : ($mairieRequests->isNotEmpty() ? 'mairie' : null)));
    $keys = array_keys($steps);
    $currentIndex = (int) array_search($current, $keys);
    $closed = $current === 'decision';
    $progress = $closed ? 100 : (int) round(($currentIndex + 0.5) / count($keys) * 100);
    $tel = $company->phone ? preg_replace('/\s+/', '', $company->phone) : null;
@endphp

{{-- En-tête : le projet, l'étape en cours et une phrase sur ce qui se passe --}}
<section class="dossier-head">
    <p class="eyebrow">{{ $dossier->procedure->label() }} · {{ $dossier->reference }}</p>
    <h1>{{ $dossier->title }}</h1>
    @if ($dossier->siteAddress())<p class="muted where">{{ $dossier->siteAddress() }}</p>@endif

    <div class="progress" aria-label="Avancement du dossier">
        <div class="progress-text">
            <strong>{{ $closed ? $dossier->status->label() : $steps[$current] }}</strong>
            <span class="muted">{{ $closed ? 'Dossier terminé' : 'Étape '.($currentIndex + 1).' sur '.count($keys) }}</span>
        </div>
        <div class="progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $progress }}"><span style="width:{{ $progress }}%"></span></div>
    </div>

    @unless ($cta)
        {{-- Rien à faire : une phrase pour rassurer. Sinon, la carte « À faire » juste en dessous parle d'elle-même. --}}
        <div class="now" role="status">
            <span class="now-label">En ce moment</span>
            <p>{{ $dossier->clientStatusMessage() }}</p>
        </div>
    @endunless
</section>

@if ($dossier->client_message)
    <div class="card note"><span class="now-label">Message de {{ $company->displayName() }}</span><p>{!! nl2br(e($dossier->client_message)) !!}</p></div>
@endif

{{-- Ce que j'attends du client : une carte par chose à faire, la première en ocre --}}
@foreach ($mairieRequests as $pr)
    <section class="card action">
        <h2>À faire : envoyer les documents demandés par la mairie</h2>
        <p>La mairie a demandé des compléments{{ $pr->requested_at ? ' le '.$pr->requested_at->format('d/m/Y') : '' }}. J'ai besoin de votre aide pour :</p>
        @if ($pr->pieces)<p><strong>{!! nl2br(e($pr->pieces)) !!}</strong></p>@endif
        @if ($pr->deadline)<p class="muted">Merci de me les envoyer avant le {{ $pr->deadline->copy()->subDays(15)->format('d/m/Y') }} : la mairie attend la réponse avant le {{ $pr->deadline->format('d/m/Y') }}.</p>@endif
        <a class="btn {{ $cta === 'mairie' && $loop->first ? 'cta' : '' }} block" href="#envoyer">Envoyer mes documents</a>
    </section>
@endforeach
@if ($pendingQuote)
    <div class="card action">
        <h2>À faire : signer votre devis</h2>
        <p>Devis {{ $pendingQuote->number }} — <strong>{{ money($pendingQuote->total_ttc) }}</strong></p>
        <a class="btn cta block" href="{{ route('portal.quotes.show', $pendingQuote) }}">Lire et signer le devis</a>
    </div>
@endif

@foreach ($plansToValidate as $plan)
    <div class="card action">
        <h2>À faire : vérifier vos plans</h2>
        <p>Les plans <strong>{{ $plan->displayName() }}</strong> sont prêts. Ouvrez-les, puis dites-moi s'ils vous conviennent.</p>
        <p>@if (\App\Services\PlanAccess::isProtected($plan))<a class="btn secondary block" href="{{ route('portal.documents.view', $plan) }}">1. Consulter les plans</a>@else<a class="btn secondary block" href="{{ route('portal.documents.download', $plan) }}" target="_blank">1. Ouvrir les plans</a>@endif</p>
        <form method="post" action="{{ route('portal.documents.validate', $plan) }}" onsubmit="return confirm('Confirmez-vous que les plans vous conviennent ?')">
            @csrf
            <button class="btn {{ $cta === 'plans' ? 'cta' : '' }} block" type="submit">2. ✓ Je valide les plans</button>
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
    {{-- Le détail et les moyens de paiement (carte, virement) sont sur la page de la facture --}}
    <div class="card action">
        <h2>À régler : {{ $inv->type->label() }} {{ $inv->number }}</h2>
        <p>Montant restant : <strong>{{ money($inv->remaining()) }}</strong>@if ($inv->due_date), avant le {{ $inv->due_date->format('d/m/Y') }}@endif.</p>
        <a class="btn {{ $cta === 'pay' && $loop->first ? 'cta' : 'secondary' }} block" href="{{ route('portal.invoices.show', $inv) }}">Voir et payer ma facture</a>
    </div>
@endforeach

@include('portal.partials.steps')

{{-- Documents --}}
<section class="card">
    <h2>Mes documents</h2>
    @php($hasDocs = $dossier->quotes->isNotEmpty() || $dossier->invoices->isNotEmpty() || $dossier->documents->isNotEmpty())
    @if ($hasDocs)
        <ul class="docs">
            @foreach ($dossier->quotes as $q)
                <li>
                    <span class="doc-icon" aria-hidden="true">📄</span>
                    <span class="doc-body"><strong>Devis {{ $q->number }}</strong><span class="muted">{{ $q->issue_date?->format('d/m/Y') }}</span></span>
                    <span class="badge {{ $q->status->value === 'signe' ? 'ok' : '' }}">{{ $q->status->label() }}</span>
                    <a class="btn small secondary" href="{{ route('portal.quotes.pdf', $q) }}" target="_blank">Ouvrir</a>
                </li>
            @endforeach
            @foreach ($dossier->invoices as $inv)
                <li>
                    <span class="doc-icon" aria-hidden="true">🧾</span>
                    <span class="doc-body"><strong>{{ $inv->type->label() }} {{ $inv->number }}</strong><span class="muted">{{ money($inv->total_ttc) }}</span></span>
                    <span class="badge {{ $inv->status->value === 'payee' ? 'ok' : 'warn' }}">{{ $inv->status->value === 'payee' ? 'Payée' : ($inv->isCreditNote() ? 'Avoir' : 'À régler') }}</span>
                    <a class="btn small secondary" href="{{ route('portal.invoices.show', $inv) }}">Ouvrir</a>
                </li>
            @endforeach
            @foreach ($dossier->documents->where('uploaded_by', '!=', 'client') as $doc)
                <li>
                    <span class="doc-icon" aria-hidden="true">{{ match ($doc->category->value) { 'photo' => '📷', 'plan' => '📐', 'recepisse', 'arrete', 'courrier_mairie' => '🏛️', default => '📎' } }}</span>
                    <span class="doc-body"><strong>{{ $doc->displayName() }}</strong><span class="muted">{{ $doc->category->label() }} · {{ $doc->created_at->format('d/m/Y') }}</span></span>
                    @if ($doc->validated_at)<span class="badge ok">Validé</span>@endif
                    @if (\App\Services\PlanAccess::isProtected($doc))
                        <a class="btn small secondary" href="{{ route('portal.documents.view', $doc) }}">Consulter</a>
                    @else
                        <a class="btn small secondary" href="{{ route('portal.documents.download', $doc) }}" target="_blank">{{ $doc->category->value === 'plan' ? 'Télécharger' : 'Ouvrir' }}</a>
                    @endif
                </li>
            @endforeach
        </ul>
        @php($sent = $dossier->documents->where('uploaded_by', 'client'))
        @if ($sent->isNotEmpty())
            {{-- Les fichiers envoyés par le client, repliés pour ne pas noyer les devis, plans et factures --}}
            <details class="more sent">
                <summary>Vos envois ({{ $sent->count() }} {{ $sent->count() > 1 ? 'fichiers' : 'fichier' }})</summary>
                <ul class="docs">
                    @foreach ($sent as $doc)
                        <li>
                            <span class="doc-icon" aria-hidden="true">{{ $doc->category->value === 'photo' ? '📷' : '📎' }}</span>
                            <span class="doc-body"><strong>{{ $doc->displayName() }}</strong><span class="muted">Envoyé le {{ $doc->created_at->format('d/m/Y') }}</span></span>
                            <a class="btn small secondary" href="{{ route('portal.documents.download', $doc) }}" target="_blank">Ouvrir</a>
                        </li>
                    @endforeach
                </ul>
            </details>
        @endif
    @else
        <p class="muted" style="margin:0">Vos devis, plans et factures apparaîtront ici.</p>
    @endif
</section>

{{-- Envoi de fichiers : facultatif, jamais une étape --}}
@if ($canUpload)
    <section class="card" id="envoyer">
        <h2>Ajouter des photos ou documents <span class="optional">Facultatif</span></h2>
        <p class="muted">Des photos de la maison et du terrain, un plan cadastral ou un croquis m'aident à aller plus vite. Ce n'est pas obligatoire : je vous demanderai ce qui manque.</p>
        @include('portal.partials.upload')
    </section>
@endif

{{-- Historique : les 5 derniers événements, le reste replié --}}
@if ($dossier->events->isNotEmpty())
    <section class="card">
        <h2>Historique</h2>
        <ol class="timeline">
            @foreach ($dossier->events->take(5) as $e)
                <li><time>{{ $e->created_at->format('d/m/Y') }}</time><span>{{ $e->message }}</span></li>
            @endforeach
        </ol>
        @if ($dossier->events->count() > 5)
            <details class="more">
                <summary>Voir tout l'historique</summary>
                <ol class="timeline">
                    @foreach ($dossier->events->slice(5) as $e)
                        <li><time>{{ $e->created_at->format('d/m/Y') }}</time><span>{{ $e->message }}</span></li>
                    @endforeach
                </ol>
            </details>
        @endif
    </section>
@endif

{{-- Une question : un appel suffit --}}
@if ($tel)
    <section class="card help">
        <div>
            <h2>Une question ?</h2>
            <p class="muted" style="margin:0">Appelez-moi, je vous réponds directement.</p>
        </div>
        <a class="btn secondary" href="tel:{{ $tel }}">📞 {{ $company->phone }}</a>
    </section>
@endif
@endsection
