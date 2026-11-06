# Nyleo — notes pour Claude

Application Laravel 13 + Filament 5 de gestion de l'activité de dessinateur (DP / PC) de Mickaël. Tout le texte visible est en français, pour des clients souvent âgés : phrases simples, gros boutons, aucune étape inutile.

- Logique métier dans `app/Services` (QuoteService, InvoiceService, DossierWorkflow, Numbering, PdfService). Les ressources Filament et les contrôleurs de l'espace client appellent ces services, jamais l'inverse.
- Statuts dans `app/Enums`. La frise client est `DossierStatus::clientSteps()`.
- Factures : une facture avec numéro ne se modifie ni ne se supprime ; correction par avoir. Les données client/entreprise sont figées dans `*_snapshot` à l'émission.
- Espace client : garde `client`, connexion par URL signée (`ClientNotification::portalUrl`). Toujours vérifier que la ressource appartient au client connecté.
- Les études RE2020 ne passent pas par Nyleo.
- Lancer `php artisan test` avant chaque push.

## Charte graphique (une seule, partout)

- Couleurs : bleu de Prusse `#1d3f91` (marque, liens, boutons de l'admin), ocre `#e8a92b` (l'action principale côté client, une par écran, texte encre dessus), encre `#101c36` (texte), pierre `#f2eee6` (fonds), traits `#dfe3eb`. Constantes PHP dans `App\Support\Brand`.
- Polices auto-hébergées (`public/fonts/site`) : Archivo pour les titres, Atkinson Hyperlegible pour le texte, IBM Plex Mono pour les petites étiquettes en capitales.
- Arrondis : 6 px pour les boutons et les champs, 14 px pour les cartes. Logo : `site.partials.logo` (SVG) ou `public/img/logo-nyleo-horizontal.png`.
- Où elle s'applique : site (`public/css/site.css`), espace client et pages d'erreur (`public/css/nyleo.css`), administration (`App\Support\Brand::filamentColors()` + `public/css/admin-nyleo.css`), e-mails (`resources/views/vendor/mail`), PDF (`resources/views/pdf/_styles.blade.php`). Toute nouvelle page reprend ces fichiers, sans nouvelle couleur.
