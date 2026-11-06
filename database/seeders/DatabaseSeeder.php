<?php

namespace Database\Seeders;

use App\Models\CompanySetting;
use App\Models\User;
use App\Support\DefaultCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        CompanySetting::current()->update([
            'phone' => '07 85 65 91 65',
            'email_signature' => "Mickaël Delpech\n07 85 65 91 65",
            'payment_terms_text' => 'Paiement par virement bancaire ou par chèque, à réception de facture. Le dossier est déposé en mairie après règlement.',
        ]);

        DefaultCatalog::seed();

        if ($email = config('nyleo.admin_email')) {
            User::firstOrCreate(['email' => $email], ['name' => 'Mickaël Delpech', 'password' => Str::password(24)]);
        }
    }
}
