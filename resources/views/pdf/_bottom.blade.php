{{-- Échéance de paiement (gauche) et totaux (droite). Variables : $doc, $dueText, $extraRows --}}
<table class="bottom">
    <tr>
        <td style="width:52%">
            @if ($dueText)
                <div class="pill">Échéance de paiement</div>
                <div class="pill-text">{!! $dueText !!}</div>
            @endif
        </td>
        <td style="width:48%">
            <table class="totals">
                <tr><td class="k">Total HT</td><td class="v">{{ money($doc->total_ht) }}</td></tr>
                <tr class="sep"><td class="k">TVA</td><td class="v">{{ money($doc->total_vat) }}</td></tr>
                <tr class="grand"><td class="k">Total TTC</td><td class="v">{{ money($doc->total_ttc) }}</td></tr>
                @foreach ($extraRows ?? [] as [$k, $v, $grand])
                    <tr class="{{ $grand ? 'grand' : '' }}"><td class="k">{{ $k }}</td><td class="v">{{ $v }}</td></tr>
                @endforeach
            </table>
        </td>
    </tr>
</table>
