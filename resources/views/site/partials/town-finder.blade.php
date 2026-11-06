{{-- Recherche de commune : renvoie vers sa page, ou vers le devis avec la commune remplie --}}
<form class="finder" action="{{ route('site.find') }}" method="get" role="search" style="margin-top:22px">
    <label for="finder-q" class="sr">Votre commune</label>
    <input id="finder-q" type="search" name="q" list="finder-list" placeholder="Votre commune, ex. Ribérac" required>
    <datalist id="finder-list">@foreach (\App\Support\Geo::communes(isset($slug) ? $slug : null)->sortBy('name') as $c)<option value="{{ $c['name'] }}"></option>@endforeach</datalist>
    <button class="btn blue" type="submit">@include('site.partials.icon', ['name' => 'search']) Trouver</button>
</form>
