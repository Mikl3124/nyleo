<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'vat_enabled' => 'boolean',
            'auto_reminders' => 'boolean',
            'bcc_enabled' => 'boolean',
            'default_vat_rate' => 'decimal:2',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'trade_name' => 'Nyleo',
            'email' => 'mickael@nyleo.fr',
            'payment_terms_days' => 0,
            'quote_footer' => 'Ce devis concerne uniquement de la prestation de service.',
            'payment_terms_text' => 'Paiement par virement bancaire ou par chèque.',
            'late_penalty_text' => "En cas de retard de paiement, des pénalités au taux de 3 fois le taux d'intérêt légal sont exigibles, ainsi qu'une indemnité forfaitaire de 40 € pour frais de recouvrement (professionnels uniquement). Pas d'escompte pour paiement anticipé.",
        ]);
    }

    public function displayName(): string
    {
        $name = $this->trade_name ?: ($this->company_name ?: config('app.name'));

        // La marque s'écrit toujours « Nyleo », même si elle a été saisie en minuscules.
        return mb_strtolower(trim((string) $name)) === 'nyleo' ? 'Nyleo' : $name;
    }

    public function fullAddress(): string
    {
        return trim(collect([$this->address, trim($this->postal_code.' '.$this->city)])->filter()->implode("\n"));
    }

    public function snapshot(): array
    {
        return $this->only([
            'company_name', 'trade_name', 'legal_form', 'share_capital', 'cgv_url', 'pdf_color', 'siret', 'rcs', 'ape_code', 'vat_number',
            'address', 'postal_code', 'city', 'phone', 'email', 'website', 'logo_path',
            'iban', 'bic', 'bank_name', 'cheque_order', 'cheque_address',
            'vat_enabled', 'vat_exemption_mention', 'payment_terms_text', 'late_penalty_text',
            'quote_footer', 'invoice_footer', 'payment_terms_days',
        ]);
    }
}
