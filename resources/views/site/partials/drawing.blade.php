{{-- Élévation au trait d'une maison avec véranda, façon planche DP5 / PCMI5 --}}
<div class="sheet">
<svg viewBox="0 0 560 360" fill="none" role="img" aria-labelledby="draw-t">
    <title id="draw-t">Dessin au trait d'une façade de maison avec véranda, avec ses cotes</title>
    <defs>
        <pattern id="hatch" width="8" height="8" patternUnits="userSpaceOnUse" patternTransform="rotate(45)"><path d="M0 0v8" stroke="rgba(255,255,255,.35)" stroke-width="1"/></pattern>
        <marker id="tick" viewBox="0 0 10 10" refX="5" refY="5" markerWidth="8" markerHeight="8" orient="auto"><path d="M2 8 8 2" stroke="#fff" stroke-width="1.4"/></marker>
    </defs>
    <g stroke="#fff" stroke-linejoin="round">
        {{-- Sol --}}
        <path d="M20 290h520" stroke-width="1.6"/>
        <rect x="20" y="290" width="520" height="14" fill="url(#hatch)" stroke="none"/>
        {{-- Maison --}}
        <path d="M70 290V150h220v140" stroke-width="2"/>
        <path d="M56 156 180 66l124 90" stroke-width="2"/>
        <path d="M62 151h236" stroke-width="1" opacity=".6"/>
        <path d="M232 104V70h22v52" stroke-width="1.6"/>
        {{-- Ouvertures --}}
        <g stroke-width="1.4">
            <rect x="96" y="178" width="44" height="54"/><path d="M118 178v54M96 205h44"/>
            <rect x="220" y="178" width="44" height="54"/><path d="M242 178v54M220 205h44"/>
            <rect x="158" y="214" width="44" height="76"/><circle cx="194" cy="254" r="2" fill="#fff"/>
            <rect x="166" y="112" width="28" height="24"/><path d="M180 112v24"/>
        </g>
        {{-- Tuiles --}}
        <g stroke-width=".8" opacity=".35"><path d="M88 134h184M110 118h140M132 102h96M154 86h52"/></g>
        {{-- Véranda (projet) --}}
        <g stroke="#94c13d">
            <path d="M290 186 430 206v84M290 290h140" stroke-width="2.2"/>
            <path d="M325 191v99M360 196v94M395 201v89M290 236h140" stroke-width="1.3"/>
            <path d="M290 186V150" stroke-width="1" stroke-dasharray="4 4"/>
        </g>
        {{-- Cotes --}}
        <g stroke-width="1" opacity=".85">
            <path d="M70 322h220M290 322h140" marker-start="url(#tick)" marker-end="url(#tick)"/>
            <path d="M70 306v22M290 306v22M430 306v22"/>
            <path d="M470 290V206M462 206h16M462 290h16M470 290v0" marker-start="url(#tick)" marker-end="url(#tick)"/>
            <path d="M510 290V66M502 66h16M502 290h16" marker-start="url(#tick)" marker-end="url(#tick)"/>
            <path d="M180 66h330" stroke-dasharray="3 5" opacity=".5"/>
            <path d="M430 206h40" stroke-dasharray="3 5" opacity=".5"/>
        </g>
    </g>
    <g fill="#fff" font-family="JetBrains Mono, monospace" font-size="11" letter-spacing=".5">
        <text x="168" y="340">10,40</text>
        <text x="345" y="340" fill="#94c13d">6,20</text>
        <text x="478" y="252">2,70</text>
        <text x="518" y="182">6,85</text>
        <text x="300" y="178" fill="#94c13d" font-size="10">PROJET</text>
        <text x="80" y="142" font-size="10" opacity=".7">EXISTANT</text>
    </g>
    {{-- Nord --}}
    <g transform="translate(40 40)" stroke="#fff" stroke-width="1.2"><circle r="16"/><path d="M0-12 6 8 0 3-6 8z" fill="#fff"/></g>
    <text x="35" y="78" fill="#fff" font-family="JetBrains Mono, monospace" font-size="10">N</text>
</svg>
<div class="cartouche"><div>Façade sud — état projeté · DP4</div><div>Éch. 1/100</div></div>
</div>
