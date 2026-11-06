<?php

namespace App\Console\Commands;

use App\Services\SuiviImporter;
use Illuminate\Console\Command;

class ImportSuivi extends Command
{
    protected $signature = 'nyleo:import-suivi {file : CSV de l\'onglet « Feuille 1 »} {--dry-run : Simuler sans rien enregistrer}';

    protected $description = 'Reprend l\'ancien fichier Excel « Suivi Plans » dans les dossiers (sans envoyer d\'e-mail)';

    public function handle(): int
    {
        $r = SuiviImporter::import($this->argument('file'), (bool) $this->option('dry-run'));
        $this->info(($this->option('dry-run') ? '[Simulation] ' : '')."{$r['created']} dossier(s) créé(s), {$r['updated']} mis à jour, {$r['clients']} client(s) créé(s), {$r['skipped']} ligne(s) ignorée(s).");
        foreach ($r['errors'] as $e) {
            $this->error($e);
        }

        return $r['errors'] ? self::FAILURE : self::SUCCESS;
    }
}
