<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('source');
            $table->unsignedBigInteger('source_id');
            $table->string('number');
            $table->json('payload');
            $table->string('content_hash', 64);
            $table->string('source_exported_at', 40);
            $table->timestamp('synced_at');
            $table->timestamps();
            $table->unique(['source', 'source_id']);
            $table->unique(['source', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_invoices');
    }
};
