<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Modèles d'e-mail d'envoi des devis et factures, modifiables dans les Paramètres (vide = texte par défaut). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->string('quote_email_subject')->nullable();
            $table->text('quote_email_body')->nullable();
            $table->string('invoice_email_subject')->nullable();
            $table->text('invoice_email_body')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn(['quote_email_subject', 'quote_email_body', 'invoice_email_subject', 'invoice_email_body']);
        });
    }
};
