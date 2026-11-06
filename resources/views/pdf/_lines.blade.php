@php($vat = ! empty($company['vat_enabled']))
<table class="lines">
    <thead>
        <tr>
            <th style="width:52%">Désignation</th>
            <th class="r">Qté</th>
            <th class="r">{{ $vat ? 'P.U. HT' : 'Prix unitaire' }}</th>
            @if ($vat)<th class="r">TVA</th>@endif
            <th class="r">{{ $vat ? 'Total HT' : 'Total' }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($lines as $line)
            <tr>
                <td><strong>{{ $line->label }}</strong>@if ($line->description)<br><span class="muted">{!! nl2br(e($line->description)) !!}</span>@endif</td>
                <td class="r">{{ rtrim(rtrim(number_format($line->quantity, 2, ',', ' '), '0'), ',') }} {{ $line->unit && $line->unit !== 'forfait' ? $line->unit : '' }}</td>
                <td class="r">{{ money($line->unit_price) }}</td>
                @if ($vat)<td class="r">{{ rtrim(rtrim(number_format($line->vat_rate, 2, ',', ''), '0'), ',') }} %</td>@endif
                <td class="r">{{ money($line->total_ht) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
<table class="totals">
    @if ($vat)
        <tr><td>Total HT</td><td class="r">{{ money($doc->total_ht) }}</td></tr>
        <tr><td>TVA</td><td class="r">{{ money($doc->total_vat) }}</td></tr>
    @endif
    <tr class="{{ empty($extraTotals) ? 'grand' : '' }}"><td>{{ $vat ? 'Total TTC' : 'Total' }}</td><td class="r">{{ money($doc->total_ttc) }}</td></tr>
    {!! $extraTotals ?? '' !!}
</table>
@if (! $vat && ! empty($company['vat_exemption_mention']))
    <p class="r small muted" style="margin-top:4px">{{ $company['vat_exemption_mention'] }}</p>
@endif
