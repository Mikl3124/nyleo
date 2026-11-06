<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Demandes de pièces complémentaires de la mairie : plusieurs par dossier, chacune avec son délai et sa date d'envoi. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('piece_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dossier_id')->constrained()->cascadeOnDelete();
            $table->date('requested_at')->nullable();
            $table->text('pieces')->nullable();
            $table->date('deadline')->nullable();
            $table->string('waiting_on')->default('moi');
            $table->date('sent_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['dossier_id', 'sent_at']);
        });

        // Reprise des demandes déjà saisies sur les dossiers.
        foreach (DB::table('dossiers')->whereNotNull('incomplete_notified_at')->orWhere('status', 'incomplet')->get() as $d) {
            DB::table('piece_requests')->insert([
                'dossier_id' => $d->id,
                'requested_at' => $d->incomplete_notified_at,
                'deadline' => $d->incomplete_deadline,
                'sent_at' => $d->status === 'incomplet' ? null : ($d->incomplete_answered_at ?? null),
                'waiting_on' => 'moi',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('piece_requests');
    }
};
