<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lignes « option » (non comprises dans le total) et remises, par ligne et sur tout le document,
 * en pourcentage ou en euros (Mickaël, 2026-11-24).
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['quote_lines', 'invoice_lines'] as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->boolean('is_option')->default(false)->after('vat_rate');
                $table->string('discount_type', 10)->nullable()->after('is_option'); // percent | amount
                $table->decimal('discount_value', 10, 2)->nullable()->after('discount_type');
            });
        }
        foreach (['quotes', 'invoices'] as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->string('discount_type', 10)->nullable()->after('subject');
                $table->decimal('discount_value', 10, 2)->nullable()->after('discount_type');
                $table->decimal('discount_amount', 10, 2)->default(0)->after('discount_value');
            });
        }
    }

    public function down(): void
    {
        foreach (['quote_lines', 'invoice_lines'] as $t) {
            Schema::table($t, fn (Blueprint $table) => $table->dropColumn(['is_option', 'discount_type', 'discount_value']));
        }
        foreach (['quotes', 'invoices'] as $t) {
            Schema::table($t, fn (Blueprint $table) => $table->dropColumn(['discount_type', 'discount_value', 'discount_amount']));
        }
    }
};
