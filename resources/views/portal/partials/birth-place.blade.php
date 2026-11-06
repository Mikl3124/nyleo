{{-- Lieu de naissance avec suggestions des communes de France ; la saisie libre reste possible. --}}
<div class="bp">
    <label for="birth_place">Lieu de naissance</label>
    <input id="birth_place" type="text" name="birth_place" value="{{ $value }}" placeholder="Commencez à taper votre commune" autocomplete="off"
        role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="birth_place_list" aria-describedby="birth_place_hint" @if ($required ?? false) required @endif>
    <ul id="birth_place_list" class="bp-list" role="listbox" hidden></ul>
    <p id="birth_place_hint" class="muted bp-hint">Choisissez votre commune dans la liste. Né(e) à l'étranger ou commune introuvable ? Écrivez simplement la ville (et le pays).</p>
</div>
@once
<style>
    .bp{position:relative}
    .bp-list{position:absolute;z-index:20;left:0;right:0;margin:4px 0 0;padding:4px;list-style:none;background:#fff;border:2px solid var(--teal-d);border-radius:12px;box-shadow:0 8px 24px rgba(16,28,54,.15);max-height:320px;overflow:auto}
    .bp-list li{padding:12px 12px;border-radius:8px;cursor:pointer;line-height:1.3}
    .bp-list li small{display:block;color:var(--muted)}
    .bp-list li[aria-selected=true],.bp-list li:hover{background:var(--teal-l)}
    .bp-list li.bp-free{font-size:.9rem;color:var(--muted);border-top:1px solid var(--line);border-radius:0 0 8px 8px}
    .bp-hint{font-size:.82rem;margin:6px 0 0}
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var input = document.getElementById('birth_place'), list = document.getElementById('birth_place_list');
    if (!input || !list) return;
    var url = @json(route('portal.communes')), timer = null, items = [], active = -1, last = '';
    function close() { list.hidden = true; list.innerHTML = ''; items = []; active = -1; input.setAttribute('aria-expanded', 'false'); }
    function choose(label) { input.value = label; close(); }
    function mark(i) {
        active = i;
        Array.prototype.forEach.call(list.children, function (li, j) { li.setAttribute('aria-selected', j === i ? 'true' : 'false'); if (j === i) li.scrollIntoView({block: 'nearest'}); });
    }
    function render(results) {
        list.innerHTML = '';
        items = results.map(function (r) { return r.label; });
        results.forEach(function (r, i) {
            var li = document.createElement('li');
            li.setAttribute('role', 'option'); li.id = 'bp_opt_' + i;
            li.textContent = r.nom;
            var s = document.createElement('small'); s.textContent = r.departement + ' - ' + r.departement_nom; li.appendChild(s);
            li.addEventListener('mousedown', function (e) { e.preventDefault(); choose(r.label); });
            list.appendChild(li);
        });
        var free = document.createElement('li');
        free.className = 'bp-free'; free.setAttribute('role', 'option');
        free.textContent = results.length ? 'Ma commune n’est pas dans la liste : garder ce que j’ai écrit' : 'Aucune commune trouvée : vous pouvez garder ce que vous avez écrit';
        free.addEventListener('mousedown', function (e) { e.preventDefault(); close(); });
        list.appendChild(free); items.push(null);
        list.hidden = false; input.setAttribute('aria-expanded', 'true'); active = -1;
    }
    input.addEventListener('input', function () {
        clearTimeout(timer);
        var q = input.value.trim();
        if (q.length < 2) { close(); return; }
        timer = setTimeout(function () {
            last = q;
            fetch(url + '?q=' + encodeURIComponent(q), {headers: {'Accept': 'application/json'}, credentials: 'same-origin'})
                .then(function (r) { return r.ok ? r.json() : []; })
                .then(function (res) { if (last === q && document.activeElement === input) render(res); })
                .catch(close);
        }, 180);
    });
    input.addEventListener('keydown', function (e) {
        if (list.hidden) return;
        if (e.key === 'ArrowDown') { e.preventDefault(); mark(Math.min(active + 1, items.length - 1)); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); mark(Math.max(active - 1, 0)); }
        else if (e.key === 'Enter' && active >= 0) { e.preventDefault(); items[active] ? choose(items[active]) : close(); }
        else if (e.key === 'Escape') { close(); }
    });
    input.addEventListener('blur', function () { setTimeout(close, 150); });
});
</script>
@endonce
