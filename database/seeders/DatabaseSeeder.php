<?php

namespace Database\Seeders;

use App\Models\CatalogItem;
use App\Models\CompanySetting;
use App\Models\User;
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

        $items = [
            ['DP', 'Déclaration préalable — véranda', "Relevé et plans de l'existant et du projet, plan de situation, plan de masse, façades, insertion graphique, notice, formulaire CERFA, dépôt du dossier en mairie et suivi de l'instruction.", 400],
            ['DP', 'Déclaration préalable — pergola', "Plans de situation, de masse et façades, insertion graphique, formulaire CERFA, dépôt et suivi de l'instruction.", 400],
            ['DP', 'Déclaration préalable — carport / abri', "Plans de situation, de masse et façades, insertion graphique, formulaire CERFA, dépôt et suivi de l'instruction.", 400],
            ['DP', 'Déclaration préalable — piscine', "Plans de situation, de masse et coupe, insertion graphique, formulaire CERFA, dépôt et suivi de l'instruction.", 400],
            ['PC', 'Permis de construire — plans et dépôt', "Pièces graphiques PCMI 1 à 8 (situation, masse, coupe, notice, façades et toitures, insertion, photographies), formulaire CERFA, dépôt et suivi de l'instruction.", 500],
            ['option', 'Impression des dossiers en A3', 'Impression et assemblage des exemplaires papier demandés par la mairie.', 0],
            ['option', 'Modification supplémentaire des plans', 'Au-delà de deux versions, par série de modifications.', 50],
            ['option', 'Complément de dossier demandé par la mairie', 'Reprise des pièces et dépôt des compléments.', 80],
        ];
        foreach ($items as $i => [$cat, $label, $desc, $price]) {
            CatalogItem::firstOrCreate(['label' => $label], [
                'category' => $cat, 'description' => $desc, 'unit_price' => $price, 'unit' => 'forfait', 'position' => $i + 1,
            ]);
        }

        if ($email = config('nyleo.admin_email')) {
            User::firstOrCreate(['email' => $email], ['name' => 'Mickaël Delpech', 'password' => Str::password(24)]);
        }
    }
}
