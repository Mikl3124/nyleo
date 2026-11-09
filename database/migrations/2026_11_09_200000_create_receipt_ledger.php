<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receipt_entries', function (Blueprint $t) {
            $t->id();
            $t->string('source_key')->unique();
            $t->string('source');
            $t->date('received_on');
            $t->string('customer');
            $t->string('document');
            $t->string('method');
            $t->string('reference')->nullable();
            $t->bigInteger('gross_minor');
            $t->bigInteger('vat_minor')->default(0);
            $t->string('currency', 3)->default('EUR');
            $t->string('category')->nullable();
            $t->json('evidence');
            $t->string('content_hash', 64);
            $t->timestamp('created_at')->useCurrent();
        });
        Schema::create('receipt_classifications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('receipt_entry_id')->unique()->constrained('receipt_entries');
            $t->string('category');
            $t->timestamps();
        });
        Schema::create('accounting_alerts', function (Blueprint $t) {
            $t->id();
            $t->string('key')->unique();
            $t->text('message');
            $t->timestamps();
        });
        Schema::create('accounting_profiles', function (Blueprint $t) {
            $t->id();
            $t->string('frequency')->default('monthly');
            $t->string('category')->nullable();
            $t->boolean('has_other_receipts')->default(true);
            $t->timestamps();
        });
        Schema::create('accounting_preparations', function (Blueprint $t) {
            $t->id();
            $t->string('period', 7);
            $t->json('snapshot');
            $t->string('content_hash', 64);
            $t->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        foreach (['accounting_preparations', 'accounting_profiles', 'accounting_alerts', 'receipt_classifications', 'receipt_entries'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
