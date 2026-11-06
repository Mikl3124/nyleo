<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Copie cachée de chaque e-mail client à Mickaël (activée par défaut, désactivable dans les Paramètres). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->boolean('bcc_enabled')->default(true);
            $table->string('bcc_email')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn(['bcc_enabled', 'bcc_email']);
        });
    }
};
