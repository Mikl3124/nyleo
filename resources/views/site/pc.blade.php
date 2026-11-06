@extends('layouts.site')
@section('title', 'Permis de construire de maison individuelle en Dordogne et Gironde')
@section('description', "Plans de permis de construire (PCMI1 à PCMI8, CERFA 13406) pour maison individuelle, extension ou garage, déposés en votre nom en Dordogne et en Gironde.")
@php($fmt = $price ? number_format($price, 0, ',', ' ').' €' : null)
@section('content')
@include('site.partials.page-hero', [
    'crumb' => 'Permis de construire', 'kicker' => 'CERFA n° 13406',
    'h1' => 'Permis de construire : <em>des plans clairs</em>, un dossier prêt à instruire.',
    'lead' => "Maison individuelle, extension ou garage : je dessine les plans du permis, je rédige la notice et je dépose le dossier en mairie en votre nom.",
    'cta' => '<a class="btn green" href="'.route('site.contact').'?demarche=PC">Demander un devis</a>',
])

<section>
    <div class="wrap split">
        <div>
            <span class="kicker">Quand est-il obligatoire ?</span>
            <h2>Les projets concernés</h2>
            <p class="lead">Le permis de construire concerne les constructions neuves et les agrandissements importants.</p>
            @if ($fmt)<div class="box green" style="margin-top:28px"><span class="muted">Dossier complet</span><div class="price"><small>À partir de</small><b>{{ $fmt }}</b></div><small class="muted">Prix fixe indiqué dans le devis{{ \App\Models\CompanySetting::current()->vat_enabled ? '.' : ', TVA non applicable.' }}</small></div>@endif
        </div>
        <div class="box">
            <ul class="checks" style="gap:16px">
                <li>@include('site.partials.icon', ['name' => 'check'])<span><b>Maison individuelle neuve</b><br><span class="muted">jusqu'à 150 m² de surface de plancher.</span></span></li>
                <li>@include('site.partials.icon', ['name' => 'check'])<span><b>Extension ou garage de plus de 20 m²</b><br><span class="muted">plus de 40 m² en zone urbaine d'un PLU.</span></span></li>
                <li>@include('site.partials.icon', ['name' => 'check'])<span><b>Grande véranda</b><br><span class="muted">dès qu'elle dépasse les seuils de la déclaration préalable.</span></span></li>
                <li>@include('site.partials.icon', ['name' => 'check'])<span><b>Au-delà de 150 m²</b><br><span class="muted">le recours à un architecte est obligatoire : je travaille avec un architecte partenaire qui signe le projet.</span></span></li>
            </ul>
        </div>
    </div>
</section>

<section class="alt">
    <div class="wrap split">
        <div>
            <span class="kicker">Ce que je fournis</span>
            <h2>Les pièces de votre permis</h2>
            <p class="lead">Les huit pièces graphiques et écrites demandées pour une maison individuelle, plus le formulaire rempli.</p>
            <div class="box" style="margin-top:24px">
                <b>Non compris</b>
                <p class="muted" style="margin:6px 0 0">L'attestation RE2020 (PCMI14-1), exigée pour une maison neuve, est réalisée à part par un bureau d'études thermiques. Les taxes d'urbanisme sont dues à l'administration.</p>
            </div>
        </div>
        <div class="plates">
            @foreach (\App\Support\SiteContent::PC_PIECES as [$code, $name, $hint])<div class="plate"><b>{{ $code }}</b><span>{{ $name }}</span><small>{{ $hint }}</small></div>@endforeach
        </div>
    </div>
</section>

<section>
    <div class="wrap">
        <div class="section-head">
            <span class="kicker">Délai</span>
            <h2>Deux mois d'instruction</h2>
            <p class="lead">Pour une maison individuelle ou son extension, la mairie a deux mois pour répondre à partir du dépôt du dossier complet, trois mois près d'un monument historique. Je surveille ces dates pour vous.</p>
        </div>
    </div>
</section>
@include('site.partials.cta')
@endsection
