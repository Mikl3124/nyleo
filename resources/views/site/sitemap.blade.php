{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($urls as $u)
    <url><loc>{{ $u }}</loc>@if ($townUpdated && \Illuminate\Support\Str::startsWith($u, $townPrefixes))<lastmod>{{ substr($townUpdated, 0, 10) }}</lastmod>@endif</url>
@endforeach
</urlset>
