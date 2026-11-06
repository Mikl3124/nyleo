<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Partenaires (installateurs, prescripteurs) qui apportent des dossiers, et suivi de leurs commissions. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('company')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            // Commission par défaut : pourcentage du montant HT du dossier, ou montant fixe par dossier.
            $table->string('commission_type')->nullable();
            $table->decimal('commission_value', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->foreignId('partner_id')->nullable()->after('source')->constrained()->nullOnDelete();
        });

        Schema::table('dossiers', function (Blueprint $table) {
            $table->foreignId('partner_id')->nullable()->after('client_id')->constrained()->nullOnDelete();
            $table->boolean('commissionable')->default(true)->after('commission_amount');
            $table->date('commission_paid_at')->nullable()->after('commission_paid');
        });

        // Les anciens prescripteurs saisis en texte deviennent des partenaires.
        $names = DB::table('dossiers')->whereNotNull('prescriber')->where('prescriber', '!=', '')
            ->where('prescriber', '!=', 'Direct')->distinct()->pluck('prescriber');
        foreach ($names as $name) {
            $id = DB::table('partners')->insertGetId(['name' => $name, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('dossiers')->where('prescriber', $name)->update(['partner_id' => $id]);
        }
    }

    public function down(): void
    {
        Schema::table('dossiers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('partner_id');
            $table->dropColumn(['commissionable', 'commission_paid_at']);
        });
        Schema::table('clients', function (Blueprint $table) {
            $table->dropConstrainedForeignId('partner_id');
        });
        Schema::dropIfExists('partners');
    }
};
