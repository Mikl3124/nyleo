<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            // Le client a demandé que le travail commence avant la fin du délai de rétractation (CGV, article 10).
            $table->boolean('early_start_requested')->default(false)->after('signer_name');
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn('early_start_requested');
        });
    }
};
