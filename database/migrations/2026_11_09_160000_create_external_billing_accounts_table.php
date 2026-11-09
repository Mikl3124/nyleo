<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_billing_accounts', function (Blueprint $t) {
            $t->id();
            $t->string('source')->unique();
            $t->json('payload');
            $t->string('source_exported_at', 40);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_billing_accounts');
    }
};
