{{-- Recherche de commune : renvoie vers sa page, ou vers le devis avec la commune remplie --}}
@if (session('status'))<div class="flash" role="status" style="margin-top:22px">{{ session('status') }}</div>@endif
<form class="finder" action="{{ route('site.find') }}" method="get" role="search" style="margin-top:22px">
    <label for="finder-q" class="sr">Votre commune</label>
    <input id="finder-q" type="search" name="q" list="finder-list" placeholder="Votre commune, ex. Ribérac" required autocomplete="off">
    {{-- Liste des communes chargée au premier clic : la page reste légère. --}}
    <datalist id="finder-list" data-src="{{ route('site.communes', isset($slug) ? ['dep' => $slug] : []) }}"></datalist>
    @isset($slug)<input type="hidden" name="dep" value="{{ $slug }}">@endisset
    @isset($kind)<input type="hidden" name="type" value="{{ $kind }}">@endisset
    <button class="btn blue" type="submit">@include('site.partials.icon', ['name' => 'search']) Trouver</button>
</form>
<script>
(function () {
    var q = document.getElementById('finder-q'), list = document.getElementById('finder-list'); if (!q || !list || q.dataset.ready) return; q.dataset.ready = 1;
    function load() {
        if (list.dataset.loaded) return; list.dataset.loaded = 1;
        fetch(list.dataset.src).then(function (r) { return r.json(); }).then(function (names) {
            var frag = document.createDocumentFragment();
            names.forEach(function (n) { var o = document.createElement('option'); o.value = n; frag.appendChild(o); });
            list.appendChild(frag);
        }).catch(function () { delete list.dataset.loaded; });
    }
    q.addEventListener('focus', load); q.addEventListener('pointerenter', load);
})();
</script>
