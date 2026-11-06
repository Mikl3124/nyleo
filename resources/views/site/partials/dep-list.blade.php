{{-- Les départements couverts, en liste compacte : nom, nombre de communes, principales villes. --}}
<div class="dep-list">
    @foreach (\App\Support\Geo::departmentSlugs() as $slug)
        @php($d = \App\Support\Geo::department($slug))
        <a class="dep-item{{ $slug === 'dordogne' ? ' home' : '' }}" href="{{ \App\Support\Geo::areaUrl($slug) }}">
            <span class="code">{{ $d['code'] }}</span>
            <span class="body"><b>{{ $d['name'] }}</b><small>{{ \App\Support\Geo::communes($slug)->count() }} communes</small><small>{{ \App\Support\Geo::pages($slug)->take(2)->pluck('name')->implode(', ') }}</small></span>
            @include('site.partials.icon', ['name' => 'arrow'])
        </a>
    @endforeach
</div>
