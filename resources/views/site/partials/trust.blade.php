@php($base = \App\Support\Geo::base())
<div class="trust">
    <div class="wrap">
        <div class="item">@include('site.partials.icon', ['name' => 'pin'])<div><b>Bureau à {{ $base['name'] }} (24)</b><span>Entre Périgord et Libournais. Je connais les règles locales.</span></div></div>
        <div class="item">@include('site.partials.icon', ['name' => 'euro'])<div><b>Prix fixe, annoncé d'avance</b><span>Dans le devis, sans surprise ni supplément caché.</span></div></div>
        <div class="item">@include('site.partials.icon', ['name' => 'pen'])<div><b>Vous payez après les plans</b><span>Le règlement intervient une fois vos plans validés.</span></div></div>
        <div class="item">@include('site.partials.icon', ['name' => 'stamp'])<div><b>Déposé en votre nom</b><span>Suivi de l'instruction jusqu'à la décision.</span></div></div>
    </div>
</div>
