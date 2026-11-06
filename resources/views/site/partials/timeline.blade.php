{{-- Délais d'instruction, à l'échelle (0 à 4 mois) --}}
<div class="timeline">
    <div class="tl-axis" aria-hidden="true"><span></span><div class="ticks"><span>1 mois</span><span>2 mois</span><span>3 mois</span><span>4 mois</span></div></div>
    @foreach ([['Déclaration préalable', 'Véranda, piscine, abri, façade…', 1], ['Permis de construire', 'Maison individuelle ou extension', 2], ['Autres permis', 'Bâtiment non résidentiel, etc.', 3]] as [$t, $s, $m])
        <div class="tl-row">
            <div class="lbl"><b>{{ $t }}</b><small>{{ $s }}</small></div>
            <div class="tl-bar" role="img" aria-label="{{ $t }} : {{ $m }} mois, {{ $m + 1 }} près d'un monument historique">
                <span class="base" style="width:calc({{ $m * 25 }}% - 2px)">{{ $m }} mois</span>
                <span class="abf" style="left:calc({{ $m * 25 }}% + 2px);width:calc(25% - 4px)">+1</span>
            </div>
        </div>
    @endforeach
    <div class="tl-legend"><span><i style="background:var(--blue)"></i>Délai légal, à partir du dossier complet</span><span><i style="background:#f6dfa8;border:1px dashed var(--ochre)"></i>En plus, près d'un monument historique (avis de l'Architecte des Bâtiments de France)</span></div>
</div>
