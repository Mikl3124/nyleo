<table class="lines">
    <thead>
        <tr>
            <th class="first" style="width:44%">Libellé</th>
            <th>Quantité</th>
            <th>Prix unitaire HT</th>
            <th>TVA</th>
            <th class="last">Total HT</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($lines as $line)
            <tr class="{{ $line->description ? '' : 'no-desc' }}">
                <td class="label">{{ $line->label }}</td>
                <td>{{ pdf_number($line->quantity) }}@if ($line->unit && $line->unit !== 'forfait') {{ $line->unit }}@endif</td>
                <td>{{ money($line->unit_price) }}</td>
                <td>{{ number_format(! empty($company['vat_enabled']) ? (float) $line->vat_rate : 0, 2, ',', ' ') }} %</td>
                <td>{{ money($line->total_ht) }}</td>
            </tr>
            @if ($line->description)
                <tr><td class="desc" colspan="5">{!! rich_description($line->description) !!}</td></tr>
            @endif
        @endforeach
    </tbody>
</table>
