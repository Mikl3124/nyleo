<div class="lanes" role="region" aria-label="Qui fait quoi à chaque étape" tabindex="0">
    <div class="lanes-grid">
        <div class="h" style="background:var(--paper)"></div>
        @foreach (\App\Support\SiteContent::LANES as $i => [$title])<div class="h"><span class="n">Étape {{ $i + 1 }}</span>{{ $title }}</div>@endforeach
        <div class="who"><i style="background:var(--ochre)"></i>Vous</div>
        @foreach (\App\Support\SiteContent::LANES as [$t, [$a, $b]])
            <div class="act you">@if ($a)<b>{{ $a }}</b>@endif<small>{{ $b }}</small></div>
        @endforeach
        <div class="who"><i style="background:var(--blue)"></i>Moi</div>
        @foreach (\App\Support\SiteContent::LANES as [$t, $you, [$a, $b]])
            <div class="act me"><b>{{ $a }}</b><small>{{ $b }}</small></div>
        @endforeach
    </div>
</div>
