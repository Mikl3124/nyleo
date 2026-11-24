{{-- Échéance de paiement (gauche) et totaux (droite). Variables : $doc, $dueText, $extraRows, $paymentQr (facultatif) --}}
<table class="bottom">
    <tr>
        <td style="width:52%">
            @if ($dueText)
                <div class="pill">Échéance de paiement</div>
                <div class="pill-text">{!! $dueText !!}</div>
            @endif
            @if (! empty($paymentQr))
                <table class="qr"><tr>
                    <td class="qr-img"><img src="{{ $paymentQr }}" alt="QR code de virement"></td>
                    <td class="qr-text"><strong>Payer par virement</strong><br>Scannez avec l'application de votre banque : le montant, l'IBAN et la référence sont déjà remplis.</td>
                </tr></table>
            @endif
        </td>
        <td style="width:48%">
            <table class="totals">
                @if ((float) $doc->discount_amount > 0)
                    <tr><td class="k">Sous-total HT</td><td class="v">{{ money($doc->subtotal()) }}</td></tr>
                    <tr><td class="k">{{ $doc->discountLabel() }}</td><td class="v">− {{ money($doc->discount_amount) }}</td></tr>
                @endif
                <tr><td class="k">Total HT</td><td class="v">{{ money($doc->total_ht) }}</td></tr>
                <tr class="sep"><td class="k">TVA</td><td class="v">{{ money($doc->total_vat) }}</td></tr>
                <tr class="grand"><td class="k">Total TTC</td><td class="v">{{ money($doc->total_ttc) }}</td></tr>
                @foreach ($extraRows ?? [] as [$k, $v, $grand])
                    <tr class="{{ $grand ? 'grand' : '' }}"><td class="k">{{ $k }}</td><td class="v">{{ $v }}</td></tr>
                @endforeach
                @if ($doc->optionsTotal() > 0)
                    <tr><td class="k" style="font-weight:normal">Options proposées (non comprises)</td><td class="v">{{ money($doc->optionsTotal()) }}</td></tr>
                @endif
            </table>
        </td>
    </tr>
</table>
