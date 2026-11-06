@props(['preheader' => null])
@php
    $company = rescue(fn () => \App\Models\CompanySetting::current(), null, false);
    $w = trim((string) $company?->website);
    $site = $w === '' ? config('app.url') : (preg_match('#^https?://#i', $w) ? $w : 'https://'.ltrim($w, '/'));
@endphp
<x-mail::layout>
{{-- En-tête --}}
<x-slot:header>
<x-mail::header :url="$site">
Nyleo
</x-mail::header>
</x-slot:header>

{{-- Corps --}}
{!! $slot !!}

{{-- Lien de secours --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Pied de page --}}
<x-slot:footer>
<x-mail::footer>
{{ $company?->displayName() ?? 'Nyleo' }} · {{ collect([$company?->phone, $company?->email, $site])->filter()->implode(' · ') }}
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
