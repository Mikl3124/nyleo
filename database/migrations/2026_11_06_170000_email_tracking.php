<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Suivi des e-mails envoyés aux clients : un enregistrement par e-mail (ouvertures, clics)
 * et un journal des actions du client (ouverture, clic, consultation, téléchargement).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_messages', function (Blueprint $table) {
            $table->id();
            $table->string('token', 40)->unique();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('dossier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('quote_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('document_id')->nullable()->constrained()->nullOnDelete();
            $table->string('to')->nullable();
            $table->string('subject')->nullable();
            $table->json('links')->nullable();
            $table->string('provider_id')->nullable()->index();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('last_opened_at')->nullable();
            $table->unsignedInteger('open_count')->default(0);
            $table->timestamp('clicked_at')->nullable();
            $table->unsignedInteger('click_count')->default(0);
            $table->timestamps();
        });

        Schema::create('client_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('email_message_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('dossier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('quote_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('document_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20);
            $table->string('label');
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();
            $table->index(['client_id', 'created_at']);
            $table->index(['quote_id', 'created_at']);
            $table->index(['invoice_id', 'created_at']);
            $table->index(['dossier_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_activities');
        Schema::dropIfExists('email_messages');
    }
};
