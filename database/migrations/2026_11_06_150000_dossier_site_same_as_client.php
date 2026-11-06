<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Case « Identique à l'adresse du client » pour l'adresse du projet (le terrain). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dossiers', function (Blueprint $table) {
            $table->boolean('site_same_as_client')->default(false)->after('site_city');
        });

        // Dossiers dont le terrain est déjà à l'adresse du client : la case est cochée d'office.
        DB::table('dossiers')->join('clients', 'clients.id', '=', 'dossiers.client_id')
            ->whereNotNull('dossiers.site_address')->where('dossiers.site_address', '!=', '')
            ->whereColumn('dossiers.site_address', 'clients.address')
            ->whereColumn('dossiers.site_postal_code', 'clients.postal_code')
            ->pluck('dossiers.id')
            ->chunk(200)
            ->each(fn ($ids) => DB::table('dossiers')->whereIn('id', $ids)->update(['site_same_as_client' => true]));
    }

    public function down(): void
    {
        Schema::table('dossiers', function (Blueprint $table) {
            $table->dropColumn('site_same_as_client');
        });
    }
};
