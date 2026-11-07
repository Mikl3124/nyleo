@php
    $natures = [
        'veranda' => ['Véranda', 'veranda'], 'extension' => ['Extension, garage', 'extension'], 'maison' => ['Maison neuve', 'maison'],
        'piscine' => ['Piscine', 'piscine'], 'pergola' => ['Pergola, carport, abri', 'pergola'], 'facade' => ['Façade, fenêtres', 'facade'],
        'photovoltaique' => ['Panneaux solaires', 'solaire'], 'cloture' => ['Clôture, portail', 'cloture'], 'autre' => ['Autre projet', 'home'],
    ];
    $townValue = old('site_city', $prefillTown ?? '');
    $currentProc = old('procedure', $procedure ?? 'AUTRE');
@endphp
<form method="post" action="{{ route('site.request') }}" class="form-card" id="demande" data-steps>
    @csrf
    @if (session('status'))<div class="flash" role="status">{{ session('status') }}</div>@endif
    @if (session('commune_status'))<div class="flash" data-info role="status">{{ session('commune_status') }}</div>@endif
    @if ($errors->any())<div class="errors" role="alert">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
    <div class="sr" aria-hidden="true"><label for="website">Ne pas remplir</label><input id="website" type="text" name="website" tabindex="-1" autocomplete="off"></div>
    {{-- Anti-robots invisible : jeton horodaté, recopié par le navigateur au premier geste (voir App\Support\BotGuard). --}}
    <input type="hidden" name="_t" value="{{ is_string(old('_t')) && old('_t') !== '' ? old('_t') : \App\Support\BotGuard::token() }}"><input type="hidden" name="_h" value="">
    @if (\App\Support\BotGuard::turnstileEnabled())
        <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.site_key') }}" data-action="demande" data-refresh-expired="auto" data-appearance="interaction-only"></div>
        @once<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>@endonce
    @endif

    <ol class="steps-nav" aria-hidden="true"><li class="on">Votre projet</li><li>Le terrain</li><li>Vos coordonnées</li></ol>

    <div class="step" data-step="1">
        <h3 tabindex="-1">Quel est votre projet ?</h3>
        <p>Choisissez ce qui s'en rapproche le plus.</p>
        <div class="tiles">
            @foreach ($natures as $k => [$l, $ico])
                <label><input type="radio" name="nature" value="{{ $k }}" @checked(old('nature', $nature ?? '') === $k)><span>@include('site.partials.icon', ['name' => $ico, 'sw' => 1.4]){{ $l }}</span></label>
            @endforeach
        </div>
        <fieldset style="border:0;padding:0;margin:22px 0 0">
            <legend style="font-weight:700;margin-bottom:10px">La démarche</legend>
            <div class="choice">
                @foreach (['DP' => 'Déclaration préalable', 'PC' => 'Permis de construire', 'AUTRE' => 'Je ne sais pas'] as $v => $l)
                    <label><input type="radio" name="procedure" value="{{ $v }}" @checked($currentProc === $v) required><span>{{ $l }}</span></label>
                @endforeach
            </div>
        </fieldset>
    </div>

    <div class="step" data-step="2">
        <h3 tabindex="-1">Où se trouve le terrain ?</h3>
        <p>Commencez à taper le nom de la commune.</p>
        <label for="site_city">Commune du terrain</label>
        <input id="site_city" type="text" name="site_city" value="{{ $townValue }}" required list="communes-list" autocomplete="off" placeholder="Ex. Ribérac">
        {{-- Liste des communes chargée au premier clic dans le champ (1 000 communes : la page reste légère). --}}
        <datalist id="communes-list" data-src="{{ route('site.communes') }}"></datalist>
        <input type="hidden" name="site_postal_code" id="site_postal_code" value="{{ old('site_postal_code') }}">
        <label for="description">Quelques mots sur le projet <small>(facultatif)</small></label>
        <textarea id="description" name="description" rows="4" placeholder="Ex. Véranda de 15 m² à l'arrière de la maison, côté jardin.">{{ old('description') }}</textarea>
    </div>

    <div class="step" data-step="3">
        <h3 tabindex="-1">Comment vous joindre ?</h3>
        <p>Je vous rappelle sous 48 h ouvrées.</p>
        <div class="grid2">
            <div><label for="last_name">Nom</label><input id="last_name" type="text" name="last_name" value="{{ old('last_name') }}" required autocomplete="family-name"></div>
            <div><label for="first_name">Prénom</label><input id="first_name" type="text" name="first_name" value="{{ old('first_name') }}" autocomplete="given-name"></div>
            <div><label for="phone">Téléphone</label><input id="phone" type="tel" name="phone" value="{{ old('phone') }}" required autocomplete="tel"></div>
            <div><label for="email">E-mail</label><input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email"></div>
        </div>
    </div>

    <div class="form-foot">
        <button class="btn ghost" type="button" data-prev hidden>Retour</button>
        <small data-hint>Vos informations servent uniquement à établir votre devis (<a href="{{ route('privacy') }}">confidentialité</a>).</small>
        <button class="btn blue" type="button" data-next hidden>Continuer @include('site.partials.icon', ['name' => 'arrow'])</button>
        <button class="btn cta" type="submit" data-submit>Envoyer ma demande @include('site.partials.icon', ['name' => 'arrow'])</button>
    </div>
</form>
<script>
(function () {
    var f = document.getElementById('demande'); if (!f || f.dataset.ready) return; f.dataset.ready = 1;
    var t = f.querySelector('input[name=_t]'), h = f.querySelector('input[name=_h]');
    function human() { h.value = t.value.split('').reverse().join(''); ['pointerdown', 'keydown', 'touchstart', 'input'].forEach(function (e) { f.removeEventListener(e, human, true); }); }
    ['pointerdown', 'keydown', 'touchstart', 'input'].forEach(function (e) { f.addEventListener(e, human, true); });
    var city = f.querySelector('#site_city'), cp = f.querySelector('#site_postal_code');
    function syncCp() { var m = /\((\d{5})\)\s*$/.exec(city.value); if (m) { cp.value = m[1]; } }
    city.addEventListener('input', function () { cp.value = ''; syncCp(); }); // une commune retapée efface l'ancien code postal
    city.addEventListener('change', syncCp); syncCp();
    var list = f.querySelector('#communes-list');
    function loadTowns() {
        if (list.dataset.loaded) return; list.dataset.loaded = 1;
        fetch(list.dataset.src).then(function (r) { return r.json(); }).then(function (names) {
            var frag = document.createDocumentFragment();
            names.forEach(function (n) { var o = document.createElement('option'); o.value = n; frag.appendChild(o); });
            list.appendChild(frag);
        }).catch(function () { delete list.dataset.loaded; });
    }
    city.addEventListener('focus', loadTowns); city.addEventListener('pointerenter', loadTowns);
    f.addEventListener('submit', function () { syncCp(); city.value = city.value.replace(/\s*\(\d{5}\)\s*$/, ''); });
    if (f.querySelector('.errors') || f.querySelector('.flash:not([data-info])')) return; // après une erreur ou un envoi, tout reste visible
    var steps = [].slice.call(f.querySelectorAll('[data-step]')), nav = [].slice.call(f.querySelectorAll('.steps-nav li'));
    var prev = f.querySelector('[data-prev]'), next = f.querySelector('[data-next]'), submit = f.querySelector('[data-submit]'), hint = f.querySelector('[data-hint]');
    var i = 0;
    document.documentElement.classList.add('js');
    function show(n) {
        i = n;
        steps.forEach(function (s, k) { s.hidden = k !== n; });
        nav.forEach(function (li, k) { li.className = k < n ? 'done' : (k === n ? 'on' : ''); });
        prev.hidden = n === 0; next.hidden = n === steps.length - 1; submit.hidden = n !== steps.length - 1; hint.hidden = n !== steps.length - 1;
    }
    next.addEventListener('click', function () {
        var bad = [].slice.call(steps[i].querySelectorAll('input,select,textarea')).find(function (el) { return !el.checkValidity(); });
        if (bad) { bad.reportValidity(); return; }
        show(i + 1); var first = steps[i].querySelector('input:not([type=hidden]),textarea'); if (first) first.focus();
    });
    prev.addEventListener('click', function () { show(i - 1); steps[i].querySelector('h3').focus(); });
    // Entrée (ou « OK » sur tablette) avant la dernière étape : passer à l'étape suivante.
    submit.addEventListener('click', function (e) { if (i < steps.length - 1) { e.preventDefault(); next.click(); } });
    // La démarche choisie (lien ?demarche=, simulateur ou clic) n'est plus devinée d'après le projet.
    var procLocked = !!f.querySelector('input[name=procedure]:checked:not([value=AUTRE])');
    f.querySelectorAll('input[name=procedure]').forEach(function (r) { r.addEventListener('change', function () { procLocked = true; }); });
    f.querySelectorAll('input[name=nature]').forEach(function (r) { r.addEventListener('change', function () {
        if (procLocked) return;
        var pc = r.value === 'maison', dp = ['piscine', 'facade', 'photovoltaique', 'cloture'].indexOf(r.value) >= 0;
        var target = f.querySelector('input[name=procedure][value=' + (pc ? 'PC' : (dp ? 'DP' : 'AUTRE')) + ']'); if (target) target.checked = true;
    }); });
    show(0);
})();
</script>
