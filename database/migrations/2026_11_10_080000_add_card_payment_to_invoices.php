<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $t) {
            // Le client peut régler cette facture par carte bancaire (Stripe Checkout).
            $t->boolean('card_payment')->default(false)->after('amount_paid');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $t) {
            $t->dropColumn('card_payment');
        });
    }
};
