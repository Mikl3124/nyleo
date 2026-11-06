<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Suivi des dossiers (remplace le fichier « Suivi Plans ») : relances, attentes, prescripteur, commission, liens. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dossiers', function (Blueprint $table) {
            $table->string('legacy_ref')->nullable()->unique();
            $table->string('prescriber')->nullable();
            $table->decimal('commission_amount', 10, 2)->nullable();
            $table->boolean('commission_paid')->default(false);
            $table->date('received_at')->nullable();
            $table->decimal('agreed_price', 10, 2)->nullable();
            $table->string('external_payment_status')->nullable();
            $table->text('follow_up_action')->nullable();
            $table->string('waiting_on')->nullable();
            $table->text('waiting_on_details')->nullable();
            $table->date('follow_up_at')->nullable();
            $table->string('priority')->default('normale');
            $table->text('missing_pieces')->nullable();
            $table->date('instruction_started_at')->nullable();
            $table->date('incomplete_answered_at')->nullable();
            $table->string('portal_url', 500)->nullable();
            $table->string('drive_url', 500)->nullable();
            $table->index('follow_up_at');
            $table->index('waiting_on');
        });

        // Les notes de suivi peuvent dépasser 255 caractères.
        Schema::table('dossier_events', function (Blueprint $table) {
            $table->text('message')->change();
        });
    }

    public function down(): void
    {
        Schema::table('dossiers', function (Blueprint $table) {
            $table->dropUnique(['legacy_ref']);
            $table->dropIndex(['follow_up_at']);
            $table->dropIndex(['waiting_on']);
            $table->dropColumn([
                'legacy_ref', 'prescriber', 'commission_amount', 'commission_paid', 'received_at', 'agreed_price',
                'external_payment_status', 'follow_up_action', 'waiting_on', 'waiting_on_details', 'follow_up_at',
                'priority', 'missing_pieces', 'instruction_started_at', 'incomplete_answered_at', 'portal_url', 'drive_url',
            ]);
        });
    }
};
