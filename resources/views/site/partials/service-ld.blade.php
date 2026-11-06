{{-- Données structurées d'une prestation (DP, PC) : prix de départ tiré du catalogue. --}}
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
    'offers' => $price ? ['@type' => 'Offer', 'priceCurrency' => 'EUR', 'priceSpecification' => ['@type' => 'PriceSpecification', 'minPrice' => $price, 'priceCurrency' => 'EUR'], 'url' => route('site.contact')] : null,
]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush
