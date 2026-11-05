<div class="footer">
    {{ collect([$company['company_name'] ?? null, $company['legal_form'] ?? null, ! empty($company['siret']) ? 'SIRET '.$company['siret'] : null, $company['rcs'] ?? null, ! empty($company['vat_number']) ? 'TVA '.$company['vat_number'] : null])->filter()->implode(' — ') }}<br>
    {{ collect([$company['address'] ?? null, trim(($company['postal_code'] ?? '').' '.($company['city'] ?? '')), $company['website'] ?? null])->filter()->implode(' — ') }}
</div>
