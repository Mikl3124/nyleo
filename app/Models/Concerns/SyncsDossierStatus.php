<?php

namespace App\Models\Concerns;

use App\Models\Dossier;
use App\Services\DossierWorkflow;

/**
 * Devis et factures : chaque changement qui compte (statut, numéro, dossier, type) remet
 * le statut du dossier d'accord avec ses documents (voir DossierWorkflow::syncFromDocuments).
 */
trait SyncsDossierStatus
{
    public static function bootSyncsDossierStatus(): void
    {
        static::saved(function (self $document) {
            if (! $document->wasRecentlyCreated && ! $document->wasChanged(['status', 'number', 'dossier_id', 'type'])) {
                return;
            }
            $document->syncDossierStatus();
            $previous = $document->getPrevious()['dossier_id'] ?? null;
            if ($document->wasChanged('dossier_id') && $previous) {
                DossierWorkflow::syncFromDocuments(Dossier::find($previous));
            }
        });

        static::deleted(fn (self $document) => $document->syncDossierStatus());
        static::restored(fn (self $document) => $document->syncDossierStatus());
    }

    public function syncDossierStatus(): void
    {
        if (! $this->dossier_id) {
            return;
        }
        $dossier = $this->relationLoaded('dossier') && $this->dossier?->id === (int) $this->dossier_id
            ? $this->dossier
            : Dossier::find($this->dossier_id);
        DossierWorkflow::syncFromDocuments($dossier);
    }
}
