@php
    $legal = collect([
        $company['legal_form'] ?? null,
        ! empty($company['share_capital']) ? 'au capital de '.trim(str_replace('€', '', $company['share_capital'])).' €' : null,
    ])->filter()->implode(' ');
    $rcs = $company['rcs'] ?? null;
@endphp
<div class="footer">
    <table>
        <tr>
            <td>
                <div class="t">{{ $company['company_name'] ?: ($company['trade_name'] ?: 'Nyleo') }}</div>
                @if ($legal)<div>{{ $legal }}</div>@endif
                @if (! empty($company['siret']))<div>SIRET : {{ $company['siret'] }}</div>@endif
                @if ($rcs)<div>{{ $rcs }}</div>@endif
                @if (! empty($company['vat_enabled']) && ! empty($company['vat_number']))<div>TVA : {{ $company['vat_number'] }}</div>@endif
            </td>
            <td>
                <div class="t">Mode de paiement</div>
                @if (! empty($company['iban']))<div>IBAN : {{ $company['iban'] }}</div>@endif
                @if (! empty($company['bic']))<div>BIC : {{ $company['bic'] }}</div>@endif
                @if (! empty($company['cheque_order']))<div>Chèque à l'ordre de : {{ $company['cheque_order'] }}</div>@endif
            </td>
        </tr>
    </table>
</div>
