<?php

namespace App\Console\Commands;

use App\Services\Billing\PermisInvoiceImporter;
use App\Services\Billing\ReceiptLedger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ImportPermisInvoices extends Command
{
    protected $signature = 'billing:import-permis {--file= : Chemin privé du fichier à importer}';

    protected $description = 'Synchronise les factures Permis-construire sans réémission ni email';

    public function handle(PermisInvoiceImporter $importer): int
    {
        $path = $this->option('file') ?: config('billing_bridge.import_path');
        if (! $path) {
            $this->info('Synchronisation non configurée.');

            return self::SUCCESS;
        }
        if (! is_file($path) || filesize($path) > 50000000) {
            $this->error('Export absent ou trop volumineux.');

            return self::FAILURE;
        }

        return Cache::lock('billing:import-permis', 300)->block(5, function () use ($path, $importer) {
            try {
                $batch = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
                $count = $importer->import($batch);
                app(ReceiptLedger::class)->sync();
                $this->info($count.' facture(s) mise(s) à jour. Aucun email envoyé.');

                return self::SUCCESS;
            } catch (\Throwable $e) {
                report($e);
                $this->error('Import refusé. Consultez le journal de l’application.');

                return self::FAILURE;
            }
        });
    }
}
