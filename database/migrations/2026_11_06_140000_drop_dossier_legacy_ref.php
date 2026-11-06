<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** L'import de l'ancien fichier Excel est retiré : l'ancien n° (URB-xxxx) ne sert plus. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dossiers', function (Blueprint $table) {
            $table->dropUnique(['legacy_ref']);
            $table->dropColumn('legacy_ref');
        });
    }

    public function down(): void
    {
        Schema::table('dossiers', function (Blueprint $table) {
            $table->string('legacy_ref')->nullable()->unique();
        });
    }
};
