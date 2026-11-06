<div class="deps">
    @foreach (['dordogne', 'gironde'] as $slug)
        @php($d = \App\Support\Geo::department($slug))
        <article class="dep-card">
            <div>
                <span class="code">Département {{ $d['code'] }}</span>
                <h3>{{ $d['name'] }}</h3>
                <ul>@foreach (\App\Support\Geo::pages($slug)->take(8) as $t)<li><a href="{{ \App\Support\Geo::url($t) }}">{{ $t['name'] }}</a></li>@endforeach</ul>
                <a class="btn ghost sm" href="{{ route('site.area', $slug) }}">Les {{ \App\Support\Geo::communes($slug)->count() }} communes</a>
            </div>
            <a href="{{ route('site.area', $slug) }}" aria-label="Carte de {{ $d['name'] }}">
                <svg class="map" viewBox="{{ $d['viewBox'] }}" aria-hidden="true"><path class="dep" d="{{ $d['outline'] }}"/>
                    @foreach (\App\Support\Geo::pages($slug) as $t)<circle class="town" cx="{{ $t['x'] }}" cy="{{ $t['y'] }}" r="1.3"/>@endforeach
                    @if (\App\Support\Geo::base()['dep'] === $slug)<circle class="base-dot" cx="{{ \App\Support\Geo::base()['x'] }}" cy="{{ \App\Support\Geo::base()['y'] }}" r="3"/>@endif
                </svg>
            </a>
        </article>
    @endforeach
</div>
