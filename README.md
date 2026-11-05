# Nyleo

Application de gestion pour **Nyleo** (nyleo.fr) : plans de déclaration préalable et de permis de construire en Dordogne et en Gironde.

- **Back-office** (`/admin`, Filament) : clients, dossiers, catalogue de prestations, devis, factures, factures d'acompte, avoirs, encaissements (virement, chèque), paramètres de l'entreprise, tableau de bord.
- **Espace client** (`/espace`) : connexion par lien envoyé par e-mail (sans mot de passe), avancement du dossier, signature du devis en ligne, validation des plans, envoi de photos, infos CERFA, téléchargement des documents et factures.
- **Site** (`/`) : page de présentation avec formulaire de demande de devis (crée le client et le dossier).

## Le parcours automatisé

1. Devis créé depuis le catalogue → « Envoyer au client » : numéro définitif, e-mail avec PDF et lien de signature.
2. Le client signe (nom + case « Bon pour accord » ; date, heure, IP et empreinte SHA-256 enregistrées). Si un acompte est prévu, la facture d'acompte part toute seule.
3. Le client envoie photos et infos CERFA depuis son espace.
4. Tu ajoutes les plans dans le dossier → « Envoyer pour validation ».
5. Le client valide → la facture finale (acomptes déduits) est émise et envoyée automatiquement.
6. Tu encaisses (virement / chèque) → le client reçoit une confirmation.
7. « Déposé en mairie » → date de décision tacite calculée (DP 1 mois, PC maison 2 mois, autres 3 mois, +1 mois ABF), client prévenu, récépissé dans son espace.
8. Pièces demandées par la mairie, décision : le client est prévenu à chaque étape.

Chaque jour à 9 h (`nyleo:reminders`) : relance des factures impayées et des devis non signés, devis expirés, alertes de délais.

Règles de facturation : numérotation continue (format `D202611-134`, `F202611-117`, `AV202611-1`, compteurs réglables pour continuer la suite d'Indy), une facture émise est figée et son PDF archivé ; on la corrige uniquement par un avoir.

## Installation locale

```sh
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
php artisan db:seed --class=DemoSeeder   # facultatif : données fictives, compte demo@nyleo.fr / demo-nyleo
php artisan make:filament-user           # ton compte admin
php artisan serve
php artisan test
```

## Mise en ligne avec Laravel Forge

1. Site `nyleo.fr`, PHP 8.3+, base MySQL, dépôt `Mikl3124/nyleo`, branche `master`.
2. Variables d'environnement : `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://nyleo.fr`, `APP_LOCALE=fr`, base MySQL, `QUEUE_CONNECTION=database`, et un service d'envoi d'e-mails (`MAIL_MAILER`, `MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS=contact@nyleo.fr`, `MAIL_FROM_NAME=Nyleo`), `NYLEO_ADMIN_EMAIL`.
3. Script de déploiement :
   ```sh
   cd $FORGE_SITE_PATH
   git pull origin $FORGE_SITE_BRANCH
   $FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader
   $FORGE_PHP artisan migrate --force
   $FORGE_PHP artisan storage:link || true
   $FORGE_PHP artisan optimize
   $FORGE_PHP artisan filament:optimize
   $FORGE_PHP artisan queue:restart
   ```
4. Daemon (worker) : `php artisan queue:work --sleep=3 --tries=3` (les e-mails partent en file d'attente).
5. Planificateur : `php artisan schedule:run` chaque minute.
6. Certificat SSL Let's Encrypt, puis `php artisan db:seed --force` (catalogue de base) et `php artisan make:filament-user`.
7. Sauvegardes quotidiennes de la base et du dossier `storage/app` (plans, photos, PDF archivés : à conserver 10 ans pour les factures).
