@extends('layouts.site')
@section('title', 'Tarifs des plans de déclaration préalable et de permis de construire')
@section('description', "Tarifs des dossiers de déclaration préalable et de permis de construire en Dordogne et en Gironde : prix fixes, dossier complet, dépôt et suivi compris.")
@php
    $vat = \App\Models\CompanySetting::current();
    $titles = ['DP' => ['Déclaration préalable', 'CERFA 13703'], 'PC' => ['Permis de construire', 'CERFA 13406'], 'option' => ['Options', 'Sur demande']];
@endphp
@section('content')
@include('site.partials.page-hero', [
    'crumb' => 'Tarifs', 'kicker' => 'Prix fixes',
    'h1' => 'Des tarifs <em>clairs</em>, annoncés avant de commencer.',
    'lead' => "Chaque projet est différent : le prix exact figure dans votre devis gratuit. Voici les tarifs de départ, dossier complet, dépôt et suivi compris.",
])
<section>
    <div class="wrap">
        @forelse ($groups as $cat => $items)
            <div class="price-group">
                <h2>{{ $titles[$cat][0] ?? ucfirst($cat) }} @if (! empty($titles[$cat][1]))<span>{{ $titles[$cat][1] }}</span>@endif</h2>
                @foreach ($items as $item)
                    <article class="price-row">
                        <div>
                            <h3>{{ $item->label }}</h3>
                            @if ($item->description)<div class="desc">{!! rich_description($item->description) !!}</div>@endif
                        </div>
                        <div class="amount">
                            @if ((float) $item->unit_price > 0)<small>{{ $cat === 'option' ? 'Forfait' : 'À partir de' }}</small><b>{{ number_format($item->unit_price, 0, ',', ' ') }} €</b>@else<small>Tarif</small><b style="font-size:1.3rem">Sur devis</b>@endif
                        </div>
                    </article>
                @endforeach
            </div>
        @empty
            <p class="lead">Les tarifs sont communiqués sur devis. <a href="{{ route('site.contact') }}">Demandez le vôtre.</a></p>
        @endforelse
        <p class="note">@if (! $vat->vat_enabled && $vat->vat_exemption_mention){{ rtrim($vat->vat_exemption_mention, '.') }}. @endif Les taxes d'urbanisme (taxe d'aménagement, redevance d'archéologie préventive) ne sont pas comprises.</p>
    </div>
</section>
@include('site.partials.cta')
@endsection
