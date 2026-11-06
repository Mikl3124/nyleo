{{-- Cartes « vos projets » : chaque carte mène au guide du projet (ProjectGuides). --}}
@php($cards = isset($guides) ? collect($guides)->map(fn ($g) => [$g['icon'], $g['name'], $g['proc'], $g['line'], $g['slug']]) : collect(\App\Support\SiteContent::PROJECTS)->map(fn ($p) => [$p[0], $p[1], $p[2], $p[3], $p[5]]))
<div class="projects">
    @foreach ($cards as [$icon, $title, $proc, $line, $slug])
        <a class="project" href="{{ route('site.project', $slug) }}">
            <div class="art"><span class="tag">{{ $proc }}</span>@include('site.partials.icon', ['name' => $icon, 'sw' => .9])</div>
            <div class="body"><h3>{{ $title }}</h3><p>{{ $line }}</p></div>
        </a>
    @endforeach
</div>
