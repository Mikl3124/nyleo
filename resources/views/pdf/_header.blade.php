<table class="head">
    <tr>
        <td style="width:55%">
            @if (! empty($company['logo_path']) && file_exists(storage_path('app/public/'.$company['logo_path'])))
                <img src="{{ storage_path('app/public/'.$company['logo_path']) }}" style="max-height:60px;max-width:200px"><br>
            @else
                <div class="brand">{{ $company['trade_name'] ?: 'Nyleo' }}</div>
            @endif
            @if (! empty($company['company_name']))<strong>{{ $company['company_name'] }}</strong>@if(!empty($company['legal_form'])) — {{ $company['legal_form'] }}@endif<br>@endif
            @if (! empty($company['address'])){{ $company['address'] }}<br>{{ $company['postal_code'] }} {{ $company['city'] }}<br>@endif
            @if (! empty($company['phone']))Tél. {{ $company['phone'] }}<br>@endif
            @if (! empty($company['email'])){{ $company['email'] }}<br>@endif
            @if (! empty($company['siret']))<span class="muted">SIRET {{ $company['siret'] }}</span>@endif
        </td>
        <td style="width:45%">
            <div class="box">
                <strong>{{ $client['name'] }}</strong><br>
                @if (! empty($client['address'])){{ $client['address'] }}<br>@endif
                {{ $client['postal_code'] ?? '' }} {{ $client['city'] ?? '' }}<br>
                @if (! empty($client['siret']))SIRET {{ $client['siret'] }}<br>@endif
                @if (! empty($client['email']))<span class="muted">{{ $client['email'] }}</span>@endif
            </div>
        </td>
    </tr>
</table>
