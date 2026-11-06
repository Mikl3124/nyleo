{{-- Données structurées d'une prestation (DP, PC), sans prix : les tarifs ne sont pas publiés. --}}
@push('head')
<script type="application/ld+json">{!! json_encode(array_filter([
    '@@context' => 'https://schema.org',
    '@type' => 'Service',
    'name' => $name,
    'serviceType' => $name,
    'description' => $description,
    'provider' => ['@id' => url('/').'#entreprise'],
    'areaServed' => collect(\App\Support\SiteContent::DEPARTMENTS)->map(fn ($d) => ['@type' => 'AdministrativeArea', 'name' => $d['name']])->values()->all(),
    'url' => url()->current(),
]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush
