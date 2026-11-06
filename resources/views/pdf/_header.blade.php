@php
    $oneLine = fn (array $p) => collect([$p['address'] ?? null, trim(($p['postal_code'] ?? '').' '.($p['city'] ?? ''))])->filter()->implode(', ');
    $logo = ! empty($company['logo_path']) && file_exists(storage_path('app/public/'.$company['logo_path'])) ? storage_path('app/public/'.$company['logo_path']) : null;
@endphp
<table class="head">
    <tr>
        @if ($logo)<td class="logo"><img src="{{ $logo }}" style="max-width:70px;max-height:60px"></td>@endif
        <td>
            <div class="party-name">{{ $company['company_name'] ?: ($company['trade_name'] ?: 'Nyleo') }}</div>
            <div class="party-line">{{ $oneLine($company) }}</div>
            @if (! empty($company['phone']) || ! empty($company['email']))
                <div class="party-line">{{ collect([$company['phone'] ?? null, $company['email'] ?? null])->filter()->implode(' · ') }}</div>
            @endif
        </td>
        <td style="text-align:right">
            <div class="party-name">{{ $client['name'] }}</div>
            <div class="party-line">{{ $oneLine($client) }}</div>
            @if (! empty($client['siret']))<div class="party-line">SIRET : {{ $client['siret'] }}</div>@endif
        </td>
    </tr>
</table>
