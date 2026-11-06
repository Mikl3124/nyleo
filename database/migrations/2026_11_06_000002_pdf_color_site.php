<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Devis et factures aux couleurs du site : le vert d'origine devient le bleu de Prusse.
 * Une couleur choisie à la main dans les Paramètres n'est pas touchée.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->string('pdf_color')->default('#1D3F91')->change();
        });
        DB::table('company_settings')
            ->where(fn ($q) => $q->whereNull('pdf_color')->orWhere('pdf_color', '')->orWhereRaw('UPPER(pdf_color) = ?', ['#94C13D']))
            ->update(['pdf_color' => '#1D3F91']);
    }

    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->string('pdf_color')->default('#94C13D')->change();
        });
        DB::table('company_settings')->whereRaw('UPPER(pdf_color) = ?', ['#1D3F91'])->update(['pdf_color' => '#94C13D']);
    }
};
