@php
    $company = rescue(fn () => \App\Models\CompanySetting::current(), null, false);
    $site = $company?->website ?: config('app.url');
    $place = $company?->city ? $company->city.($company->postal_code ? ' ('.substr($company->postal_code, 0, 2).')' : '') : null;
    $contact = collect([$place, $company?->phone, $company?->email])->filter()->implode(' · ');
@endphp
<x-mail::layout>
{{-- En-tête : logo Nyleo --}}
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
**{{ $company?->displayName() ?? 'Nyleo' }}** · plans de permis de construire et déclarations préalables<br>
{{ $contact }}{{ $contact ? ' · ' : '' }}[{{ preg_replace('#^https?://(www\.)?#', '', rtrim($site, '/')) }}]({{ $site }})
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
