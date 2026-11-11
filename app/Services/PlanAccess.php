<?php

namespace App\Services;

use App\Enums\DocumentCategory;
use App\Models\Document;
use App\Models\Dossier;
use App\Notifications\ClientNotification;

/**
 * Plans protégés : tant que les factures du dossier ne sont pas réglées, le client consulte ses plans
 * en ligne (images avec filigrane, voir PlanPreview) sans pouvoir les télécharger.
 * Le déblocage est automatique au paiement (carte, virement ou chèque encaissé dans l'admin)
 * ou manuel (Dossier::plans_unlocked_at). Ne jamais dire au client que c'est lié au paiement.
 */
class PlanAccess
{
    /** Le client peut télécharger les plans originaux de ce dossier. */
    public static function unlocked(Dossier $dossier): bool
    {
        if ($dossier->plans_unlocked_at) {
            return true;
        }

        // Factures émises du dossier (hors avoirs et factures annulées) : il en faut une, toutes réglées.
        $invoices = $dossier->invoices()->whereNotNull('number')->where('type', '!=', 'avoir')
            ->where('status', '!=', 'annulee')->pluck('status')->map(fn ($s) => $s instanceof \BackedEnum ? $s->value : $s);

        return $invoices->isNotEmpty() && $invoices->every(fn ($s) => $s === 'payee');
    }

    /** Ce document ne se télécharge pas : il se consulte seulement, avec filigrane. */
    public static function isProtected(Document $document): bool
    {
        return $document->category === DocumentCategory::Plan
            && $document->uploaded_by !== 'client'
            && $document->dossier
            && ! self::unlocked($document->dossier);
    }

    /** Lien « Consulter les plans » pour un e-mail : connexion automatique à l'espace client puis visionneuse. */
    public static function viewerUrl(Document $document): string
    {
        return ClientNotification::portalUrl($document->dossier->client, route('portal.documents.view', $document, false));
    }
}
