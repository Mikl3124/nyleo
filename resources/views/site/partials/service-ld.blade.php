{{-- Données structurées d'une prestation (DP, PC), sans prix : les tarifs ne sont pas publiés. --}}
@push('head')
<script type="application/ld+json">{!! json_encode(array_filter([
    '@@context' => 'https://schema.org',
    '@type' => 'Service',
    'name' => $name,
    'serviceType' => $name,
    'description' => $description,
    'provider' => ['@id' => url('/').'#entreprise'],
    'areaServed' => [['@type' => 'AdministrativeArea', 'name' => 'Dordogne'], ['@type' => 'AdministrativeArea', 'name' => 'Gironde']],
    'url' => url()->current(),
]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush
