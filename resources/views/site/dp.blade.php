@extends('layouts.site')
@section('title', 'Déclaration préalable de travaux en Dordogne et Gironde')
@section('description', "Dossier de déclaration préalable complet (DP1 à DP8, CERFA 13703) pour véranda, pergola, carport, piscine, clôture ou façade, déposé en votre nom en Dordogne et en Gironde.")
@php($fmt = $price ? number_format($price, 0, ',', ' ').' €' : null)
@section('content')
@include('site.partials.page-hero', [
    'crumb' => 'Déclaration préalable', 'kicker' => 'CERFA n° 13703',
    'h1' => 'Déclaration préalable : <em>votre dossier complet</em>, déposé pour vous.',
    'lead' => "Pour une véranda, une pergola, un carport, une piscine, une clôture ou un changement de façade, je prépare toutes les pièces et je dépose la déclaration en mairie en votre nom.",
    'cta' => '<a class="btn green" href="'.route('site.contact').'?demarche=DP">Demander un devis</a>',
])

<section>
    <div class="wrap split">
        <div>
            <span class="kicker">Quand est-elle obligatoire ?</span>
            <h2>Les cas les plus courants</h2>
            <p class="lead">Une déclaration préalable suffit pour les projets de taille modeste. Au-delà, il faut un permis de construire.</p>
            @if ($fmt)<div class="box green" style="margin-top:28px"><span class="muted">Dossier complet</span><div class="price"><small>À partir de</small><b>{{ $fmt }}</b></div><small class="muted">Prix fixe indiqué dans le devis{{ \App\Models\CompanySetting::current()->vat_enabled ? '.' : ', TVA non applicable.' }}</small></div>@endif
        </div>
        <div class="box">
            <ul class="checks" style="gap:16px">
                <li>@include('site.partials.icon', ['name' => 'check'])<span><b>Construction ou abri de 5 à 20 m²</b><br><span class="muted">carport, abri de jardin, pergola couverte, véranda.</span></span></li>
                <li>@include('site.partials.icon', ['name' => 'check'])<span><b>Extension jusqu'à 40 m²</b><br><span class="muted">en zone urbaine d'un PLU, tant que la maison ne dépasse pas 150 m² au total.</span></span></li>
                <li>@include('site.partials.icon', ['name' => 'check'])<span><b>Piscine de 10 à 100 m²</b><br><span class="muted">non couverte, ou avec un abri de moins de 1,80 m.</span></span></li>
                <li>@include('site.partials.icon', ['name' => 'check'])<span><b>Modification de l'aspect extérieur</b><br><span class="muted">fenêtres, portes, enduit, toiture, panneaux solaires.</span></span></li>
                <li>@include('site.partials.icon', ['name' => 'check'])<span><b>Clôture ou portail</b><br><span class="muted">si la commune l'impose ou près d'un monument historique.</span></span></li>
                <li>@include('site.partials.icon', ['name' => 'check'])<span><b>Changement de destination</b><br><span class="muted">sans travaux sur la structure, par exemple une grange transformée en habitation.</span></span></li>
            </ul>
        </div>
    </div>
</section>

<section class="alt">
    <div class="wrap split">
        <div>
            <span class="kicker">Ce que je fournis</span>
            <h2>Les pièces de votre dossier</h2>
            <p class="lead">Chaque pièce porte son code officiel. Selon le projet, certaines ne sont pas exigées : je ne vous fais faire que l'utile.</p>
            <ul class="checks" style="margin-top:20px">
                <li>@include('site.partials.icon', ['name' => 'check']) Deux versions des plans pour vos corrections</li>
                <li>@include('site.partials.icon', ['name' => 'check']) Dépôt en ligne ou en mairie, en votre nom</li>
                <li>@include('site.partials.icon', ['name' => 'check']) Suivi jusqu'à la décision, compléments compris si l'erreur vient de moi</li>
            </ul>
        </div>
        <div class="plates">
            @foreach (\App\Support\SiteContent::DP_PIECES as [$code, $name, $hint])<div class="plate"><b>{{ $code }}</b><span>{{ $name }}</span><small>{{ $hint }}</small></div>@endforeach
        </div>
    </div>
</section>

<section>
    <div class="wrap">
        <div class="section-head">
            <span class="kicker">Délai</span>
            <h2>Un mois d'instruction</h2>
            <p class="lead">La mairie a un mois pour répondre à partir du dépôt du dossier complet, deux mois si le terrain est proche d'un monument historique. Vous suivez chaque étape depuis votre espace client.</p>
        </div>
    </div>
</section>
@include('site.partials.cta')
@endsection
