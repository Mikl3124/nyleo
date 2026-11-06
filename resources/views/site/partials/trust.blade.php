@php($base = \App\Support\Geo::base())
<div class="trust">
    <div class="wrap">
        <div class="item">@include('site.partials.icon', ['name' => 'pin'])<div><b>Bureau à {{ $base['name'] }} (24)</b><span>Entre Périgord et Libournais. Je connais les règles locales.</span></div></div>
        <div class="item">@include('site.partials.icon', ['name' => 'phone'])<div><b>Un seul interlocuteur</b><span>Vous parlez à la personne qui dessine vos plans.</span></div></div>
        <div class="item">@include('site.partials.icon', ['name' => 'pen'])<div><b>Deux versions des plans</b><span>Vous relisez et vous corrigez avant le dépôt.</span></div></div>
        <div class="item">@include('site.partials.icon', ['name' => 'stamp'])<div><b>Déposé en votre nom</b><span>Suivi de l'instruction jusqu'à la décision.</span></div></div>
    </div>
</div>
