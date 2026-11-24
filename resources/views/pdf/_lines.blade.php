{{-- Lignes : remise par ligne sous le prix, lignes « option » marquées et non comprises dans le total. --}}
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
            <tr class="{{ $line->description ? '' : 'no-desc' }} {{ $line->is_option ? 'option' : '' }}">
                <td class="label">{{ $line->label }}@if ($line->is_option) <span class="tag">Option</span>@endif</td>
                <td>{{ pdf_number($line->quantity) }}@if ($line->unit && $line->unit !== 'forfait') {{ $line->unit }}@endif</td>
                <td>{{ money($line->unit_price) }}@if ($line->discountLabel())<br><span class="was">{{ $line->discountLabel() }}</span>@endif</td>
                <td>{{ number_format(! empty($company['vat_enabled']) ? (float) $line->vat_rate : 0, 2, ',', ' ') }} %</td>
                <td>@if ($line->discountLabel())<span class="was">{{ money($line->grossAmount()) }} − {{ money($line->discountAmount()) }}</span><br>@endif{{ money($line->total_ht) }}</td>
            </tr>
            @if ($line->description)
                <tr class="{{ $line->is_option ? 'option' : '' }}"><td class="desc" colspan="5">{!! rich_description($line->description) !!}</td></tr>
            @endif
        @endforeach
    </tbody>
</table>
@if ($lines->contains(fn ($l) => $l->is_option))
    <div class="options-note">Les lignes marquées « option » ne sont pas comprises dans le total.</div>
@endif
