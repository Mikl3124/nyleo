<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->string('share_capital')->nullable()->after('legal_form');
            $table->string('cgv_url')->nullable()->after('website');
            $table->string('pdf_color')->default('#94C13D')->after('logo_path');
        });

        // Modèle de Mickaël : paiement « À réception » et mention de prestation de service.
        DB::table('company_settings')->where('payment_terms_days', 15)->update(['payment_terms_days' => 0]);
        DB::table('company_settings')->whereNull('quote_footer')->update(['quote_footer' => 'Ce devis concerne uniquement de la prestation de service.']);
    }

    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn(['share_capital', 'cgv_url', 'pdf_color']);
        });
    }
};
