@extends('layouts.site')
@php
    use App\Support\SiteContent;
    use App\Support\Geo;
    $c = \App\Models\CompanySetting::current();
    $tel = $c->phone ? preg_replace('/\s+/', '', $c->phone) : null;
    $base = Geo::base();
@endphp
@section('content')
<section class="hero">
    <div class="wrap">
        <div>
            <span class="eyebrow">Dessinateur en Dordogne · Gironde</span>
            <h1>Vos plans de permis et de déclaration préalable, <span class="hl">près de chez vous</span>.</h1>
            <p class="lead">Je dessine vos plans, je remplis le formulaire et je dépose le dossier en mairie à votre place. Depuis {{ $base['name'] }}, pour toute la Dordogne et la Gironde.</p>
            <div class="hero-cta">
                @if ($tel)<a class="btn cta" href="tel:{{ $tel }}">@include('site.partials.icon', ['name' => 'phone']) <span>Appeler le {{ $c->phone }}</span></a>@endif
                <a class="btn ghost" href="{{ route('site.contact') }}">Demander un devis gratuit</a>
            </div>
            <ul class="proof">
                <li>@include('site.partials.icon', ['name' => 'check']) Un seul interlocuteur, du devis à la décision</li>
                <li>@include('site.partials.icon', ['name' => 'check']) Dépôt en mairie en votre nom</li>
                <li>@include('site.partials.icon', ['name' => 'check']) Suivi jusqu'à la décision</li>
            </ul>
        </div>
        <div class="map-card">
            @include('site.partials.map-region')
            <div class="legend"><span><i style="background:var(--ochre);border:1px solid var(--ink)"></i>Le bureau, à {{ $base['name'] }}</span><span><i style="background:#fff;border:1px solid var(--blue)"></i>{{ Geo::pages()->count() }} communes avec leur page</span><span>Cercles : 25, 50, 100 km</span></div>
            <div class="badge-local"><span class="av">M</span><span><b>Mickaël, votre interlocuteur</b><small>Un seul contact, du devis à la décision</small></span></div>
        </div>
    </div>
</section>

@include('site.partials.trust')

<section>
    <div class="wrap">
        <div class="sec-head row">
            <div>
                <span class="eyebrow">Simulateur</span>
                <h2>Déclaration préalable ou permis ? Faites le test.</h2>
                <p class="lead">Indiquez votre projet et la surface : vous voyez tout de suite la démarche, le formulaire et le délai.</p>
            </div>
        </div>
        @include('site.partials.simulator')
    </div>
</section>

<section class="stone">
    <div class="wrap">
        <div class="sec-head row">
            <div>
                <span class="eyebrow">Vos projets</span>
                <h2>Ce que je dessine chaque semaine</h2>
            </div>
            <a class="link-arrow" href="{{ route('site.projects') }}">Tous les projets @include('site.partials.icon', ['name' => 'arrow'])</a>
        </div>
        @include('site.partials.project-cards')
    </div>
</section>

<section>
    <div class="wrap">
        <div class="sec-head">
            <span class="eyebrow">Qui fait quoi</span>
            <h2>Vous n'avez presque rien à faire</h2>
            <p class="lead">Cinq étapes. En orange ce que vous faites, en bleu ce dont je m'occupe.</p>
        </div>
        @include('site.partials.lanes')
    </div>
</section>

<section class="stone">
    <div class="wrap split">
        <div>
            <span class="eyebrow">Le dossier</span>
            <h2>Toutes les pièces demandées, dessinées pour vous</h2>
            <p class="lead">Un dossier incomplet, c'est un mois de perdu. Chaque planche porte son code officiel et elle est vérifiée avant le dépôt.</p>
            <p style="margin-top:22px"><a class="link-arrow" href="{{ route('site.dp') }}">La déclaration préalable @include('site.partials.icon', ['name' => 'arrow'])</a></p>
            <p><a class="link-arrow" href="{{ route('site.pc') }}">Le permis de construire @include('site.partials.icon', ['name' => 'arrow'])</a></p>
        </div>
        @include('site.partials.plates', ['pieces' => SiteContent::DP_PIECES])
    </div>
</section>

<section>
    <div class="wrap">
        <div class="sec-head">
            <span class="eyebrow">Délais</span>
            <h2>Combien de temps avant la réponse ?</h2>
            <p class="lead">Le délai court à partir du dossier complet. Sans réponse à la fin du délai, l'accord est en principe tacite. Je surveille ces dates pour vous.</p>
        </div>
        @include('site.partials.timeline')
    </div>
</section>

<section class="stone">
    <div class="wrap">
        <div class="sec-head row">
            <div>
                <span class="eyebrow">Secteurs</span>
                <h2>Toute la Dordogne et toute la Gironde</h2>
                <p class="lead">Je connais les règlements et les services instructeurs des deux départements. Trouvez votre commune.</p>
            </div>
        </div>
        @include('site.partials.dep-cards')
    </div>
</section>

<section>
    <div class="wrap">
        <div style="max-width:820px">
            <span class="eyebrow">Qui suis-je</span>
            <h2>Un dessinateur du coin, joignable en direct</h2>
            <p class="lead">Je m'appelle Mickaël. Mon bureau est à {{ $base['name'] }}, près de Mussidan, entre Périgueux et Libourne. Vous m'appelez, vous me parlez de votre projet, et c'est moi qui dessine et qui dépose votre dossier.</p>
            <div class="values">
                <div><b>Un seul interlocuteur</b><span>Pas de standard ni de plateforme : vous parlez à la personne qui dessine.</span></div>
                <div><b>Des plans soignés</b><span>Clairs pour la mairie, compréhensibles pour vous.</span></div>
                <div><b>Toutes les pièces</b><span>Chaque planche est vérifiée avant le dépôt.</span></div>
                <div><b>Simple pour tous</b><span>Pas de mot de passe, de gros boutons, et le téléphone pour le reste.</span></div>
            </div>
            <p style="margin-top:24px"><a class="link-arrow" href="{{ route('site.about') }}">En savoir plus @include('site.partials.icon', ['name' => 'arrow'])</a></p>
        </div>
    </div>
</section>

<section class="stone">
    <div class="wrap">
        <div class="sec-head"><span class="eyebrow">Questions fréquentes</span><h2>Vous vous demandez peut-être…</h2></div>
        <div class="faq">@foreach (array_slice(SiteContent::FAQ, 0, 5) as [$q, $a])<details><summary>{{ $q }}</summary><p>{{ $a }}</p></details>@endforeach</div>
        <p style="margin-top:22px"><a class="link-arrow" href="{{ route('site.faq') }}">Toutes les questions @include('site.partials.icon', ['name' => 'arrow'])</a></p>
    </div>
</section>

<section id="contact">
    <div class="wrap contact">
        <div>
            <span class="eyebrow">Devis gratuit</span>
            <h2>Parlez-moi de votre projet</h2>
            <p class="lead">Trois questions, et je vous rappelle pour en parler. Vous préférez le téléphone ? Appelez directement.</p>
            @include('site.partials.contact-info')
        </div>
        @include('site.partials.contact-form')
    </div>
</section>
@endsection
