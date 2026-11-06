{{-- Données structurées d'une page commune : Service + FAQPage. Attend $town, $service, $faq. --}}
@php($dep = \App\Support\Geo::department($town['dep']))
@push('head')
<script type="application/ld+json">{!! json_encode([
    '@@context' => 'https://schema.org',
    '@graph' => array_values(array_filter([
        [
            '@type' => 'Service',
            'name' => $service.' '.\App\Support\Geo::a($town['name']),
            'serviceType' => $service,
            'provider' => ['@id' => url('/').'#entreprise'],
            'areaServed' => ['@type' => 'City', 'name' => $town['name'], 'address' => ['@type' => 'PostalAddress', 'postalCode' => $town['cp'][0] ?? null, 'addressLocality' => $town['name'], 'addressRegion' => $dep['name'], 'addressCountry' => 'FR']],
            'url' => url()->current(),
        ],
        $faq ? ['@type' => 'FAQPage', 'mainEntity' => collect($faq)->map(fn ($f) => ['@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]]])->all()] : null,
    ])),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush
