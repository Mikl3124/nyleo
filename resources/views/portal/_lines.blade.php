{{-- Lignes d'un devis / d'une facture dans l'espace client : remises et options affichées clairement. --}}
@foreach ($lines as $l)
    <tr class="{{ $l->is_option ? 'is-option' : '' }}"><td><strong>{{ $l->label }}</strong>@if ($l->is_option)<span class="opt-tag">Option</span>@endif
        @if ($l->description)<br><span class="muted">{!! nl2br(e($l->description)) !!}</span>@endif
        @if ((float) $l->quantity != 1)<br><span class="muted">{{ rtrim(rtrim(number_format($l->quantity, 2, ',', ' '), '0'), ',') }} × {{ money($l->unit_price) }}</span>@endif
        @if ($l->discountLabel())<br><span class="muted">{{ $l->discountLabel() }} : − {{ money($l->discountAmount()) }}</span>@endif
        @if ($l->is_option)<br><span class="muted">Non comprise dans le total.</span>@endif</td>
        <td class="r">{{ money($l->total_ht) }}</td></tr>
@endforeach
@if ((float) $doc->discount_amount > 0)
    <tr><td>Sous-total</td><td class="r">{{ money($doc->subtotal()) }}</td></tr>
    <tr><td>{{ $doc->discountLabel() }}</td><td class="r">− {{ money($doc->discount_amount) }}</td></tr>
@endif
