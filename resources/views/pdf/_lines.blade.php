{{-- Lignes : colonne « Remise » (montant déduit, et taux si en %), lignes « option » marquées et non comprises dans le total. --}}
@php($vatCol = ! empty($company['vat_enabled']))
{{-- Colonne TVA seulement si l'entreprise est assujettie (Mickaël, 2026-11-24 : inutile sinon). --}}
<table class="lines">
    <thead>
        <tr>
            <th class="first" style="width:40%">Libellé</th>
            <th>Quantité</th>
            <th>Prix unitaire HT</th>
            <th>Remise</th>
            @if ($vatCol)<th>TVA</th>@endif
            <th class="last">Total HT</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($lines as $line)
            <tr class="{{ $line->description ? '' : 'no-desc' }} {{ $line->is_option ? 'option' : '' }}">
                <td class="label">{{ $line->label }}@if ($line->is_option) <span class="tag">Option</span>@endif</td>
                <td>{{ pdf_number($line->quantity) }}@if ($line->unit && $line->unit !== 'forfait') {{ $line->unit }}@endif</td>
                <td>{{ money($line->unit_price) }}</td>
                <td>@if ($line->discountLabel())− {{ money($line->discountAmount()) }}@if ($line->discount_type === 'percent')<br><span class="was">{{ pdf_number($line->discount_value) }} %</span>@endif @else — @endif</td>
                @if ($vatCol)<td>{{ number_format((float) $line->vat_rate, 2, ',', ' ') }} %</td>@endif
                <td>{{ money($line->total_ht) }}</td>
            </tr>
            @if ($line->description)
                <tr class="{{ $line->is_option ? 'option' : '' }}"><td class="desc" colspan="{{ $vatCol ? 6 : 5 }}">{!! rich_description($line->description) !!}</td></tr>
            @endif
        @endforeach
    </tbody>
</table>
@if ($lines->contains(fn ($l) => $l->is_option))
    <div class="options-note">Les lignes marquées « option » ne sont pas comprises dans le total.</div>
@endif
