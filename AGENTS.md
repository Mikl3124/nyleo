# Nyleo — notes pour Claude

Application Laravel 13 + Filament 5 de gestion de l'activité de dessinateur (DP / PC) de Mickaël. Tout le texte visible est en français, pour des clients souvent âgés : phrases simples, gros boutons, aucune étape inutile.

- Logique métier dans `app/Services` (QuoteService, InvoiceService, DossierWorkflow, Numbering, PdfService). Les ressources Filament et les contrôleurs de l'espace client appellent ces services, jamais l'inverse.
- Statuts dans `app/Enums`. La frise client est `DossierStatus::clientSteps()`.
- Factures : une facture avec numéro ne se modifie ni ne se supprime ; correction par avoir. Les données client/entreprise sont figées dans `*_snapshot` à l'émission.
- Espace client : garde `client`, connexion par URL signée (`ClientNotification::portalUrl`). Toujours vérifier que la ressource appartient au client connecté.
- Les études RE2020 ne passent pas par Nyleo.
- Lancer `php artisan test` avant chaque push.
