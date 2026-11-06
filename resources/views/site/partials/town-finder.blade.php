{{-- Recherche de commune : renvoie vers sa page, ou vers le devis avec la commune remplie --}}
@if (session('status'))<div class="flash" role="status" style="margin-top:22px">{{ session('status') }}</div>@endif
<form class="finder" action="{{ route('site.find') }}" method="get" role="search" style="margin-top:22px">
    <label for="finder-q" class="sr">Votre commune</label>
    <input id="finder-q" type="search" name="q" list="finder-list" placeholder="Votre commune, ex. Ribérac" required>
    <datalist id="finder-list">@foreach (\App\Support\Geo::communes(isset($slug) ? $slug : null)->sortBy('name') as $c)<option value="{{ $c['name'] }} ({{ $c['cp'][0] ?? '' }})"></option>@endforeach</datalist>
    @isset($slug)<input type="hidden" name="dep" value="{{ $slug }}">@endisset
    <button class="btn blue" type="submit">@include('site.partials.icon', ['name' => 'search']) Trouver</button>
</form>
