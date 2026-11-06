{{-- Termes et conditions. Variable : $footerText --}}
<div class="terms">
    <div class="t">Termes et conditions</div>
    @if (! empty($footerText)){!! nl2br(e($footerText)) !!}<br>@endif
    @if (empty($company['vat_enabled']) && ! empty($company['vat_exemption_mention'])){{ rtrim($company['vat_exemption_mention'], '.') }}.<br>@endif
    @foreach ($extraTerms ?? [] as $t){{ $t }}<br>@endforeach
    @if (! empty($company['cgv_url']))Consultez l'ensemble des conditions générales de vente sur notre site web {{ $company['cgv_url'] }}@endif
</div>
