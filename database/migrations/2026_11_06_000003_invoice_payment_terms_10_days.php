<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Mickaël laisse désormais 10 jours pour payer les factures.
        // Seul le réglage change : les factures déjà émises gardent leur échéance.
        DB::table('company_settings')->where('payment_terms_days', 0)->update(['payment_terms_days' => 10]);
    }

    public function down(): void
    {
        DB::table('company_settings')->where('payment_terms_days', 10)->update(['payment_terms_days' => 0]);
    }
};
