<div class="projects">
    @foreach (\App\Support\SiteContent::PROJECTS as [$icon, $title, $proc, $line])
        <a class="project" href="{{ str_contains($proc, 'PC') && ! str_contains($proc, 'DP') ? route('site.pc') : route('site.dp') }}">
            <div class="art"><span class="tag">{{ $proc }}</span>@include('site.partials.icon', ['name' => $icon, 'sw' => .9])</div>
            <div class="body"><h3>{{ $title }}</h3><p>{{ $line }}</p></div>
        </a>
    @endforeach
</div>
