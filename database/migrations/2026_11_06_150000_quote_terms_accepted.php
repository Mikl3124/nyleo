<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            // Signature en ligne : CGV acceptées et mandat donné pour déposer la demande et en assurer le suivi.
            $table->timestamp('terms_accepted_at')->nullable()->after('early_start_requested');
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn('terms_accepted_at');
        });
    }
};
