<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dossiers', function (Blueprint $t) {
            // Téléchargement des plans débloqué à la main (sinon : automatiquement quand les factures sont réglées).
            $t->timestamp('plans_unlocked_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('dossiers', function (Blueprint $t) {
            $t->dropColumn('plans_unlocked_at');
        });
    }
};
