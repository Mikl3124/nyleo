<?php

use App\Models\CatalogItem;
use App\Support\DefaultCatalog;
use Illuminate\Database\Migrations\Migration;

/**
 * Ajoute l'étude thermique RE2020 au catalogue en ligne, sans toucher aux prestations existantes
 * (Mickaël modifie le catalogue directement en production). Rien n'est fait si elle existe déjà.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! CatalogItem::query()->exists()) {
            return; // catalogue vide : le seeder crée tout, étude comprise
        }
        $item = DefaultCatalog::thermalStudy();
        if (CatalogItem::query()->where('label', $item['label'])->exists()) {
            return;
        }
        CatalogItem::create($item + ['position' => (int) CatalogItem::query()->max('position') + 1, 'active' => true]);
    }

    public function down(): void
    {
        // On ne supprime pas une prestation que Mickaël a pu modifier depuis.
    }
};
