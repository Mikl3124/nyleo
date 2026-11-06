{{-- Simulateur : quelle démarche pour mon projet ? (calcul dans le navigateur, sans envoi) --}}
@php
    $priceDp = $prices['DP'] ?? null;
    $pricePc = $prices['PC'] ?? null;
@endphp
<div class="sim" id="simulateur" data-dp="{{ $priceDp ? number_format($priceDp, 0, ',', ' ').' €' : '' }}" data-pc="{{ $pricePc ? number_format($pricePc, 0, ',', ' ').' €' : '' }}" data-contact="{{ route('site.contact') }}">
    <form class="sim-form" onsubmit="return false">
        <fieldset>
            <legend>Votre projet</legend>
            <div class="chips">
                @foreach ([['extension', 'Véranda, extension, garage accolé', 'veranda'], ['construction', 'Abri, carport, garage séparé, pergola', 'carport'], ['piscine', 'Piscine', 'piscine'], ['maison', 'Maison neuve', 'maison'], ['aspect', 'Façade, fenêtres, solaire', 'facade'], ['cloture', 'Clôture, portail', 'cloture']] as [$v, $l, $i])
                    <label><input type="radio" name="sim_type" value="{{ $v }}" @checked($loop->first)><span>@include('site.partials.icon', ['name' => $i, 'sw' => 2.2]) {{ $l }}</span></label>
                @endforeach
            </div>
        </fieldset>
        <fieldset data-for="surface">
            <legend><label for="sim_surface" style="margin:0">Surface créée (emprise au sol ou surface de plancher)</label></legend>
            <div class="range">
                <div class="range-track">
                    <input id="sim_surface" type="range" min="0" max="160" step="1" value="18">
                    <div class="sim-scale" aria-hidden="true">@foreach ([0, 40, 80, 120, 160] as $v)<span style="--v:{{ $v }}">{{ $v }}{{ $v === 160 ? ' m²' : '' }}</span>@endforeach</div>
                </div>
                <output for="sim_surface" id="sim_surface_out">18 m²</output>
            </div>
        </fieldset>
        <fieldset data-for="zone">
            <legend>Le terrain est en zone urbaine (U) d'un PLU ?</legend>
            <div class="chips">
                <label><input type="radio" name="sim_zone" value="oui"><span>Oui</span></label>
                <label><input type="radio" name="sim_zone" value="non"><span>Non</span></label>
                <label><input type="radio" name="sim_zone" value="nsp" checked><span>Je ne sais pas</span></label>
            </div>
        </fieldset>
        <fieldset style="margin-bottom:0">
            <legend>Près d'un monument historique (moins de 500 m) ?</legend>
            <div class="chips">
                <label><input type="radio" name="sim_abf" value="oui"><span>Oui</span></label>
                <label><input type="radio" name="sim_abf" value="non" checked><span>Non ou je ne sais pas</span></label>
            </div>
        </fieldset>
    </form>
    <div class="sim-out" aria-live="polite">
        <span class="k">Démarche probable</span>
        <span class="v" id="sim_result">Déclaration préalable</span>
        <p id="sim_why">Entre 5 et 20 m², une déclaration préalable suffit.</p>
        <dl>
            <dt>Délai d'instruction</dt><dd id="sim_delay">1 mois</dd>
            <dt>Formulaire</dt><dd id="sim_cerfa">CERFA n° 13703</dd>
            <dt>Dossier complet</dt><dd id="sim_price">{{ $priceDp ? 'à partir de '.number_format($priceDp, 0, ',', ' ').' €' : 'sur devis' }}</dd>
        </dl>
        <a class="btn cta" id="sim_cta" href="{{ route('site.contact') }}">Demander mon devis @include('site.partials.icon', ['name' => 'arrow'])</a>
    </div>
</div>
<p class="sim-note">Indication générale selon le code de l'urbanisme. Le PLU de votre commune peut être plus strict : je vérifie toujours avant le devis.</p>
<script>
(function () {
    var root = document.getElementById('simulateur'); if (!root) return;
    var $ = function (id) { return document.getElementById(id); };
    var val = function (n) { var e = root.querySelector('input[name="' + n + '"]:checked'); return e ? e.value : null; };
    function compute() {
        var t = val('sim_type'), s = +$('sim_surface').value, zone = val('sim_zone'), abf = val('sim_abf') === 'oui';
        $('sim_surface_out').textContent = s + ' m²';
        root.querySelector('[data-for=surface]').hidden = (t === 'aspect' || t === 'cloture' || t === 'maison');
        root.querySelector('[data-for=zone]').hidden = (t !== 'extension');
        var r, why;
        if (t === 'maison') { r = 'PC'; why = "Une maison neuve demande un permis de construire. Si la surface de plancher ou l'emprise au sol dépasse 150 m², ou pour une société (SCI…), un architecte doit signer le projet."; }
        else if (t === 'aspect') { r = 'DP'; why = "Changer l'aspect extérieur (fenêtres, enduit, toiture, panneaux solaires) demande une déclaration préalable."; }
        else if (t === 'cloture') { r = 'DP'; why = "Une clôture demande une déclaration préalable si la commune l'impose ou près d'un monument historique. Sinon, aucune démarche."; }
        else if (t === 'piscine') {
            if (s <= 10) { r = 'RIEN'; why = "Un bassin de 10 m² ou moins ne demande en général aucune démarche (hors secteur protégé)."; }
            else if (s <= 100) { r = 'DP'; why = "Un bassin de plus de 10 m² et jusqu'à 100 m², non couvert ou avec un abri de moins de 1,80 m : déclaration préalable."; }
            else { r = 'PC'; why = "Au-delà de 100 m² de bassin, il faut un permis de construire."; }
        } else {
            var limit = (t === 'extension' && zone === 'oui') ? 40 : 20;
            if (s <= 5) { r = 'RIEN'; why = "Moins de 5 m² : en général aucune démarche, sauf secteur protégé."; }
            else if (s <= limit) { r = 'DP'; why = s > 20 ? "En zone urbaine d'un PLU, une extension jusqu'à 40 m² passe en déclaration préalable (si la maison ne dépasse pas 150 m² au total)." : "Entre 5 et 20 m², une déclaration préalable suffit."; }
            else if (t === 'extension' && zone === 'nsp' && s <= 40) { r = 'DP ou PC'; why = "Entre 20 et 40 m², tout dépend de la zone du PLU. Je vérifie pour vous sur le plan de la commune."; }
            else { r = 'PC'; why = "Au-delà de " + limit + " m², il faut un permis de construire."; }
        }
        if (r === 'RIEN' && abf) { r = 'DP'; why = "Près d'un monument historique, même une petite construction ou un petit bassin demande une déclaration préalable."; }
        var names = { 'DP': 'Déclaration préalable', 'PC': 'Permis de construire', 'RIEN': 'Pas de démarche', 'DP ou PC': 'DP ou permis' };
        var months = r === 'PC' ? 2 : (r === 'RIEN' ? 0 : 1);
        $('sim_result').textContent = names[r];
        $('sim_why').textContent = why;
        var extra = abf ? 1 : 0, note = abf ? ' (avis des Bâtiments de France)' : '';
        $('sim_delay').textContent = r === 'RIEN' ? '—' : (r === 'DP ou PC' ? (1 + extra) + ' ou ' + (2 + extra) + ' mois' : (months + extra) + ' mois') + note;
        $('sim_cerfa').textContent = r === 'PC' ? 'CERFA n° 13406' : (r === 'DP' ? 'CERFA n° 13703' : (r === 'RIEN' ? '—' : '13703 ou 13406'));
        var p = r === 'PC' ? root.dataset.pc : root.dataset.dp;
        $('sim_price').textContent = r === 'RIEN' ? 'conseil gratuit' : (p ? 'à partir de ' + p : 'sur devis');
        var d = r === 'PC' ? 'PC' : (r === 'DP' ? 'DP' : 'AUTRE');
        $('sim_cta').href = root.dataset.contact + '?demarche=' + d;
    }
    root.addEventListener('input', compute); root.addEventListener('change', compute); compute();
})();
</script>
