@extends('layouts.site')
@section('title', 'Votre projet : déclaration préalable ou permis de construire ?')
@section('description', "Véranda, extension, piscine, abri, carport, maison, fenêtres, panneaux solaires, clôture, grange : la démarche d'urbanisme projet par projet, en Dordogne et dans les départements voisins.")
@section('content')
@include('site.partials.page-hero', [
    'crumbs' => 'Vos projets', 'kicker' => 'Projet par projet',
    'h1' => 'Quelle démarche pour <span class="hl">votre projet</span> ?',
    'lead' => "Choisissez votre projet : vous saurez s'il faut une déclaration préalable, un permis de construire ou rien du tout, quelles pièces fournir et combien de temps attendre.",
])
<section>
    <div class="wrap">
        @include('site.partials.project-cards', ['guides' => \App\Support\ProjectGuides::all()])
    </div>
</section>
<section class="stone">
    <div class="wrap">
        <div class="sec-head"><span class="eyebrow">Simulateur</span><h2>Ou faites le test directement</h2></div>
        @include('site.partials.simulator')
    </div>
</section>
@include('site.partials.cta')
@endsection
