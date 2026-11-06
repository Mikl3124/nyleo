@extends('layouts.site')
@section('title', 'Plans de permis de construire et déclaration préalable en '.$dep['name'].' ('.$dep['code'].')')
@section('description', "Dessin des plans et dépôt de vos dossiers de déclaration préalable et de permis de construire en {$dep['name']} : ".implode(', ', array_slice($dep['towns'], 0, 6)).' et alentours.')
@section('content')
@include('site.partials.page-hero', [
    'crumb' => 'Secteurs / '.$dep['name'], 'kicker' => $dep['name'].' · '.$dep['code'],
    'h1' => 'Plans et dossiers d\'urbanisme <em>en '.e($dep['name']).'</em>',
    'lead' => $dep['intro'],
    'cta' => '<a class="btn green" href="'.route('site.contact').'">Demander un devis</a>',
])
<section>
    <div class="wrap split">
        <div>
            <span class="kicker">Communes</span>
            <h2>Partout en {{ $dep['name'] }}</h2>
            <p class="lead">Tout se fait à distance : un appel, vos photos et le cadastre suffisent dans la plupart des cas. J'interviens dans toutes les communes du département, notamment :</p>
        </div>
        <ul class="towns" style="margin-top:0">@foreach ($dep['towns'] as $t)<li>{{ $t }}</li>@endforeach</ul>
    </div>
</section>
<section class="alt">
    <div class="wrap">
        <div class="duo">
            <article class="offer"><span class="code">DP</span><h3>Déclaration préalable</h3><p class="muted">Véranda, pergola, carport, piscine, clôture, façade, panneaux solaires.</p><div class="foot"><span class="muted">Instruction : 1 mois</span><a class="btn ghost" href="{{ route('site.dp') }}">En savoir plus</a></div></article>
            <article class="offer"><span class="code">PC</span><h3>Permis de construire</h3><p class="muted">Maison individuelle, extension, garage.</p><div class="foot"><span class="muted">Instruction : 2 mois</span><a class="btn ghost" href="{{ route('site.pc') }}">En savoir plus</a></div></article>
        </div>
    </div>
</section>
@include('site.partials.cta')
@endsection
