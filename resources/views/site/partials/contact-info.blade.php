@php
    $c = \App\Models\CompanySetting::current();
    $base = \App\Support\Geo::base();
@endphp
<ul class="info-list">
    @if ($c->phone)<li><span class="ico">@include('site.partials.icon', ['name' => 'phone'])</span><span><b>Par téléphone, en direct</b><a href="tel:{{ preg_replace('/\s+/', '', $c->phone) }}">{{ $c->phone }}</a></span></li>@endif
    @if ($c->email)<li><span class="ico">@include('site.partials.icon', ['name' => 'mail'])</span><span><b>Par e-mail</b><a href="mailto:{{ $c->email }}" style="font-size:1rem">{{ $c->email }}</a></span></li>@endif
    <li><span class="ico">@include('site.partials.icon', ['name' => 'clock'])</span><span><b>Réponse rapide</b>Rappel sous 48 h ouvrées</span></li>
    <li><span class="ico">@include('site.partials.icon', ['name' => 'pin'])</span><span><b>Bureau à {{ $base['name'] }} ({{ $base['cp'][0] ?? '24' }})</b>Dordogne et départements voisins, à distance ou sur place si besoin</span></li>
</ul>
