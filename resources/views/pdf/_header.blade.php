@php
    $oneLine = fn (array $p) => collect([$p['address'] ?? null, trim(($p['postal_code'] ?? '').' '.($p['city'] ?? ''))])->filter()->implode(', ');
    // Logo envoyé dans les Paramètres, sinon le logo Nyleo du site.
    $custom = ! empty($company['logo_path']) && file_exists(storage_path('app/public/'.$company['logo_path'])) ? storage_path('app/public/'.$company['logo_path']) : null;
    $brand = public_path('img/logo-nyleo-horizontal.png');
@endphp
<table class="head">
    <tr>
        <td class="brand">
            @if ($custom)
                <img src="{{ $custom }}" style="max-width:120px;max-height:56px">
            @elseif (file_exists($brand))
                <img src="{{ $brand }}" style="height:42px">
            @endif
        </td>
        <td class="issuer">
            <div class="party-name">{{ $company['company_name'] ?: ($company['trade_name'] ?: 'Nyleo') }}</div>
            <div class="party-line">{{ $oneLine($company) }}</div>
            @if (! empty($company['phone']) || ! empty($company['email']))
                <div class="party-line">{{ collect([$company['phone'] ?? null, $company['email'] ?? null])->filter()->implode(' · ') }}</div>
            @endif
            @if (! empty($company['website']))<div class="party-line">{{ preg_replace('#^https?://#', '', $company['website']) }}</div>@endif
        </td>
    </tr>
</table>
<div class="accent"></div>
<table class="head2">
    <tr>
        <td>
            <p class="doc-title">{{ $docTitle ?? '' }}</p>
            @if (! empty($docDates))<div class="dates">@foreach ($docDates as $d)<div>{{ $d }}</div>@endforeach</div>@endif
        </td>
        <td class="client">
            <div class="label">Client</div>
            <div class="party-name">{{ $client['name'] }}</div>
            <div class="party-line">{{ $oneLine($client) }}</div>
            @if (! empty($client['siret']))<div class="party-line">SIRET : {{ $client['siret'] }}</div>@endif
        </td>
    </tr>
</table>
