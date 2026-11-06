@extends('layouts.site')
@php
    use App\Support\SiteContent;
    $c = \App\Models\CompanySetting::current();
    $fmt = fn ($p) => $p ? number_format($p, 0, ',', ' ').' €' : null;
@endphp
@section('content')
<section class="hero">
    <div class="wrap">
        <div>
            <span class="kicker">Dordogne · Gironde</span>
            <h1>Vos plans de permis et de déclaration préalable, <em>dessinés et déposés</em> pour vous.</h1>
            <p class="lead">Véranda, pergola, piscine, extension ou maison : je dessine les plans, je remplis le formulaire CERFA et je dépose votre dossier en mairie. Vous n'avez qu'à signer.</p>
            <div class="hero-cta">
                <a class="btn green" href="{{ route('site.contact') }}">Demander un devis gratuit @include('site.partials.icon', ['name' => 'arrow'])</a>
                @if ($c->phone)<a class="btn light" href="tel:{{ preg_replace('/\s+/', '', $c->phone) }}">@include('site.partials.icon', ['name' => 'phone']) {{ $c->phone }}</a>@endif
            </div>
            <ul class="checks">
                <li>@include('site.partials.icon', ['name' => 'check']) Prix fixe annoncé dans le devis, sans surprise</li>
                <li>@include('site.partials.icon', ['name' => 'check']) Dossier déposé en votre nom, en ligne ou en mairie</li>
                <li>@include('site.partials.icon', ['name' => 'check']) Suivi de l'instruction jusqu'à la décision</li>
            </ul>
        </div>
        <div class="drawing">@include('site.partials.drawing')</div>
    </div>
</section>

<section>
    <div class="wrap">
        <div class="section-head">
            <span class="kicker">Deux démarches</span>
            <h2>Déclaration préalable ou permis de construire : <em>je m'occupe de tout.</em></h2>
            <p class="lead">Le choix dépend surtout de la surface créée. Au premier appel, je vous dis laquelle s'applique à votre projet.</p>
        </div>
        <div class="duo">
            <article class="offer">
                <span class="code">CERFA 13703</span>
                <h3>Déclaration préalable</h3>
                <p class="muted">Pour les petits projets : de 5 à 20 m² en général, jusqu'à 40 m² pour une extension en zone urbaine.</p>
                <ul>
                    <li>Véranda, pergola, carport, abri de jardin</li>
                    <li>Piscine, clôture, portail</li>
                    <li>Fenêtres, façade, toiture, panneaux solaires</li>
                </ul>
                <div class="foot">
                    <div class="price">@if ($fmt($prices['DP'] ?? null))<small>À partir de</small><b>{{ $fmt($prices['DP']) }}</b>@else<small>Sur devis</small>@endif</div>
                    <a class="btn ghost" href="{{ route('site.dp') }}">En savoir plus</a>
                </div>
            </article>
            <article class="offer">
                <span class="code">CERFA 13406</span>
                <h3>Permis de construire</h3>
                <p class="muted">Pour une maison neuve, ou une extension ou un garage de plus de 20 m² (40 m² en zone urbaine).</p>
                <ul>
                    <li>Maison individuelle de moins de 150 m²</li>
                    <li>Extension, garage, grande véranda</li>
                    <li>Au-delà de 150 m², avec un architecte partenaire</li>
                </ul>
                <div class="foot">
                    <div class="price">@if ($fmt($prices['PC'] ?? null))<small>À partir de</small><b>{{ $fmt($prices['PC']) }}</b>@else<small>Sur devis</small>@endif</div>
                    <a class="btn ghost" href="{{ route('site.pc') }}">En savoir plus</a>
                </div>
            </article>
        </div>
    </div>
</section>

<section class="alt">
    <div class="wrap">
        <div class="section-head">
            <span class="kicker">Vos projets</span>
            <h2>Les projets que je dessine le plus souvent</h2>
        </div>
        <div class="projects">
            @foreach (SiteContent::PROJECTS as [$icon, $title, $proc, $line])
                <div class="project">
                    @include('site.partials.icon', ['name' => $icon])
                    <div><h3>{{ $title }}</h3><p>{{ $line }}</p><span class="tag">{{ $proc }}</span></div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section>
    <div class="wrap">
        <div class="section-head">
            <span class="kicker">Comment ça se passe</span>
            <h2>Cinq étapes, <em>et vous ne vous occupez que de la première.</em></h2>
        </div>
        <ol class="steps">
            @foreach (SiteContent::STEPS as [$t, $d])<li><h3>{{ $t }}</h3><p>{{ $d }}</p></li>@endforeach
        </ol>
    </div>
</section>

<section class="alt">
    <div class="wrap split">
        <div>
            <span class="kicker">Le dossier complet</span>
            <h2>Toutes les pièces demandées par la mairie, <em>prêtes à déposer.</em></h2>
            <p class="lead">Un dossier incomplet, c'est un mois de perdu. Chaque pièce est dessinée selon la nomenclature officielle et vérifiée avant le dépôt.</p>
            <p><a class="btn ghost" href="{{ route('site.pricing') }}">Voir le détail des tarifs</a></p>
        </div>
        <div class="plates">
            @foreach (SiteContent::DP_PIECES as [$code, $name, $hint])
                <div class="plate"><b>{{ $code }}</b><span>{{ $name }}</span><small>{{ $hint }}</small></div>
            @endforeach
        </div>
    </div>
</section>

<section>
    <div class="wrap">
        <div class="section-head">
            <span class="kicker">Délais d'instruction</span>
            <h2>Combien de temps pour avoir la réponse ?</h2>
        </div>
        <table class="delays">
            <thead><tr><th>Démarche</th><th>Délai légal</th><th>Près d'un monument historique</th></tr></thead>
            <tbody>
                <tr><td>Déclaration préalable</td><td><b>1 mois</b></td><td>2 mois</td></tr>
                <tr><td>Permis de construire, maison individuelle ou extension</td><td><b>2 mois</b></td><td>3 mois</td></tr>
                <tr><td>Autres permis de construire</td><td><b>3 mois</b></td><td>4 mois</td></tr>
            </tbody>
        </table>
        <p class="note">Le délai court à partir du moment où le dossier est complet. Sans réponse de la mairie à la fin du délai, l'accord est en principe tacite. Je surveille ces dates pour vous.</p>
    </div>
</section>

<section class="alt">
    <div class="wrap">
        <div class="section-head">
            <span class="kicker">Secteurs</span>
            <h2>En Dordogne et en Gironde</h2>
            <p class="lead">Je connais les règlements locaux et les services instructeurs des deux départements. Tout se fait à distance, par téléphone et en ligne.</p>
        </div>
        <div class="areas">
            @foreach (SiteContent::DEPARTMENTS as $slug => $dep)
                <article class="area">
                    <span class="num" aria-hidden="true">{{ $dep['code'] }}</span>
                    <h3 style="font-size:1.6rem">{{ $dep['name'] }}</h3>
                    <ul class="towns">@foreach (array_slice($dep['towns'], 0, 10) as $t)<li>{{ $t }}</li>@endforeach<li>et alentours</li></ul>
                    <a class="btn ghost sm" href="{{ route('site.area', $slug) }}">Mon intervention en {{ $dep['name'] }}</a>
                </article>
            @endforeach
        </div>
    </div>
</section>

<section>
    <div class="wrap">
        <div class="section-head">
            <span class="kicker">Questions fréquentes</span>
            <h2>Vous vous demandez peut-être…</h2>
        </div>
        <div class="faq">
            @foreach (array_slice(SiteContent::FAQ, 0, 4) as [$q, $a])<details><summary>{{ $q }}</summary><p>{{ $a }}</p></details>@endforeach
        </div>
        <p style="margin-top:22px"><a href="{{ route('site.faq') }}">Toutes les questions @include('site.partials.icon', ['name' => 'arrow'])</a></p>
    </div>
</section>

<section class="alt" id="contact">
    <div class="wrap contact">
        <div>
            <span class="kicker">Devis gratuit</span>
            <h2>Parlez-moi de votre projet</h2>
            <p class="lead">Remplissez ce formulaire ou appelez-moi directement. Je vous rappelle pour en parler, puis je vous envoie un devis clair.</p>
            @include('site.partials.contact-info')
        </div>
        @include('site.partials.contact-form')
    </div>
</section>
@endsection
