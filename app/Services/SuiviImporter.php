<?php

namespace App\Services;

use App\Enums\DossierStatus;
use App\Enums\ExternalPaymentStatus;
use App\Enums\Priority;
use App\Enums\WaitingOn;
use App\Models\Client;
use App\Models\Dossier;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Reprise de l'ancien fichier « Suivi Plans » (onglet Feuille 1, exporté en CSV).
 *
 * Les colonnes sont lues par leur titre. Une ligne déjà importée (même « ID dossier »)
 * est mise à jour au lieu d'être recréée : on peut relancer l'import sans doublon.
 * Aucun e-mail n'est envoyé aux clients.
 */
class SuiviImporter
{
    /** @return array{created:int, updated:int, skipped:int, clients:int, errors:array<string>} */
    public static function import(string $path, bool $dryRun = false): array
    {
        $report = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'clients' => 0, 'errors' => [], 'seen' => []];
        $rows = self::readCsv($path);

        DB::beginTransaction();
        try {
            foreach ($rows as $i => $row) {
                try {
                    self::importRow($row, $report);
                } catch (\Throwable $e) {
                    $report['errors'][] = 'Ligne '.($i + 2).' ('.($row['NOM'] ?? '?').') : '.$e->getMessage();
                }
            }
            $dryRun ? DB::rollBack() : DB::commit();
            unset($report['seen']);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return $report;
    }

    /** @return array<int, array<string, string>> */
    public static function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if (! $handle) {
            throw new \RuntimeException('Fichier illisible');
        }
        $first = fgets($handle);
        $delimiter = substr_count($first, ';') > substr_count($first, ',') ? ';' : ',';
        rewind($handle);

        $header = null;
        $rows = [];
        while (($line = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
            if ($header === null) {
                $header = array_map(fn ($h) => trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $h)), $line);

                continue;
            }
            $row = [];
            foreach ($header as $k => $name) {
                if ($name !== '' && ! isset($row[$name])) {
                    $row[$name] = trim((string) ($line[$k] ?? ''));
                }
            }
            if (($row['NOM'] ?? '') !== '' || ($row['ID dossier'] ?? '') !== '') {
                $rows[] = $row;
            }
        }
        fclose($handle);

        if (! $header || ! in_array('NOM', $header, true)) {
            throw new \RuntimeException('Colonne « NOM » introuvable : exportez bien l\'onglet « Feuille 1 » en CSV.');
        }

        return $rows;
    }

    private static function importRow(array $r, array &$report): void
    {
        $get = fn (string $col) => ($v = trim($r[$col] ?? '')) === '' ? null : $v;

        $name = $get('NOM');
        $legacy = $get('ID dossier');
        // Lignes préenregistrées sans nom : le demandeur est en tête de la référence (« SCI Fugo — … »).
        if (! $name && ($ref = $get('Référence officielle / projet'))) {
            $name = Str::limit(trim(Str::before(Str::before($ref, ' — '), ' ; ')), 60, '') ?: null;
        }
        // Ligne sans ID : on lui en donne un stable, tiré du nom, pour que l'import reste rejouable.
        $legacy ??= 'XLS-'.Str::upper(Str::slug((string) $name));
        // Deux lignes différentes avec le même ID (CHAUVET maison 1 et 2) : la seconde devient URB-0120-2.
        if ($legacy) {
            $key = mb_strtoupper((string) $name);
            $seen = $report['seen'][$legacy] ?? [];
            if ($seen && ! in_array($key, $seen, true)) {
                $report['seen'][$legacy][] = $key;
                $legacy .= '-'.count($report['seen'][$legacy]);
            } elseif (! $seen) {
                $report['seen'][$legacy] = [$key];
            }
        }
        if (! $name && ! $legacy) {
            $report['skipped']++;

            return;
        }

        [$city, $dept] = self::splitCity($get('VILLE'));
        $statusLabel = $get('Statut de pilotage') ?? $get('STATUT') ?? $get('Étape production · calcul');
        $status = self::status($statusLabel, $get('STATUT'));
        $procedure = match (strtoupper((string) $get('Type DP / PC'))) {
            'DP' => 'DP',
            'PC' => 'PC',
            default => 'AUTRE',
        };

        // Un dossier accordé ou refusé peut garder une action (DAACT, suite d'un refus…).
        $stillToDo = ! in_array($status->value, Dossier::closedStatuses(), true) || $get('Prochaine action') !== null;
        $deposit = self::date($get('Date dépôt confirmée')) ?? self::date($get('DATE DÉPÔT'));

        $dossier = Dossier::withTrashed()->where('legacy_ref', $legacy)->first();
        $isNew = $dossier === null;

        if ($isNew) {
            // Même nom ET même commune : même client (deux BRUNET de communes différentes restent distincts).
            $client = Client::query()->whereRaw('upper(last_name) = ?', [mb_strtoupper($name ?? 'Inconnu')])
                ->where('city', $city)->whereNull('email')->first();
            if (! $client) {
                $client = Client::create([
                    'last_name' => $name ?? 'Inconnu',
                    'city' => $city,
                    'source' => $get('PRESCRIPTEUR'),
                    'notes' => 'Client repris de l\'ancien suivi Excel.',
                ]);
                $report['clients']++;
            }
            $dossier = new Dossier(['client_id' => $client->id]);
        }

        $typeLabel = $procedure === 'AUTRE' ? 'Dossier' : $procedure;
        $prescriber = self::prescriber($get('PRESCRIPTEUR'));
        $commission = $get('COMMISSION');

        $dossier->fill([
            'legacy_ref' => $legacy,
            'title' => $dossier->title ?: trim($typeLabel.' '.($name ?? '').($city ? ' — '.$city : '')),
            'procedure' => $dossier->exists ? $dossier->procedure : $procedure,
            'site_city' => $dossier->site_city ?: $city,
            'department' => $dossier->department ?: $dept,
            'status' => $status,
            'prescriber' => $prescriber,
            'commission_amount' => is_numeric($commission) ? (float) $commission : null,
            'commission_paid' => $commission !== null && Str::lower($commission) === 'payé',
            'received_at' => self::date($get('REÇU LE')),
            'deposit_date' => $deposit,
            'instruction_started_at' => self::date($get('Début instruction confirmé')),
            'decision_due_date' => self::date($get('Fin instruction notifiée')),
            'decision_date' => self::date($get('Date décision')),
            'incomplete_notified_at' => self::date($get('Date demande de pièces')),
            'incomplete_answered_at' => self::date($get('Date réponse complément')),
            'agreed_price' => is_numeric($get('PRIX')) ? (float) $get('PRIX') : null,
            'external_payment_status' => self::payment($get('ETAT')),
            'portal_url' => self::url($get('PORTAIL')),
            'drive_url' => self::url($get('Drive maître')) ?? self::url($get('DOSSIER')),
            'follow_up_action' => $get('Prochaine action'),
            'waiting_on' => self::waitingOn($get('En attente de'), $status, $get('Prochaine action') !== null),
            'waiting_on_details' => $get('En attente de'),
            'follow_up_at' => $stillToDo ? self::date($get('Revue / relance interne')) : null,
            'priority' => self::priority($get('Priorité Eisenhower')),
            'missing_pieces' => $get('Pièces manquantes / à vérifier'),
            'internal_notes' => self::notes($get, $dossier->internal_notes),
        ]);
        if (! $dossier->decision_due_date && $dossier->deposit_date && ! in_array($status->value, Dossier::closedStatuses(), true)) {
            $dossier->decision_due_date = $dossier->computeDecisionDueDate();
        }
        $dossier->save();

        if ($isNew) {
            $dossier->log('import', "Dossier repris de l'ancien suivi Excel ({$legacy})");
            $report['created']++;
        } else {
            $report['updated']++;
        }
    }

    public static function status(?string $pilotage, ?string $statut = null): DossierStatus
    {
        $s = Str::lower(Str::ascii((string) $pilotage));

        return match (true) {
            str_starts_with($s, 'accord') => DossierStatus::Accorde,
            str_starts_with($s, 'refus') => DossierStatus::Refuse,
            str_contains($s, 'complement') => DossierStatus::Incomplet,
            str_starts_with($s, 'depose'), str_starts_with($s, 'instruction') => DossierStatus::EnInstruction,
            str_starts_with($s, 'validation client'), str_starts_with($s, 'envoye') => DossierStatus::PlansAValider,
            str_starts_with($s, 'production'), str_starts_with($s, 'controle'), str_starts_with($s, 'a modifier') => DossierStatus::PlansEnCours,
            str_starts_with($s, 'nouveau'), str_starts_with($s, 'a qualifier') => DossierStatus::Nouveau,
            $statut !== null && $pilotage !== $statut => self::status($statut),
            default => DossierStatus::Nouveau,
        };
    }

    private static function payment(?string $etat): ?ExternalPaymentStatus
    {
        return match (Str::lower(Str::ascii((string) $etat))) {
            'paye' => ExternalPaymentStatus::Paye,
            'facture' => ExternalPaymentStatus::Facture,
            'devis' => ExternalPaymentStatus::Devis,
            'accepte' => ExternalPaymentStatus::Accepte,
            'refuse' => ExternalPaymentStatus::Refuse,
            default => null,
        };
    }

    private static function priority(?string $p): Priority
    {
        $p = Str::lower(Str::ascii((string) $p));

        return match (true) {
            str_contains($p, 'urgent') && ! str_contains($p, 'non urgent') => Priority::Urgente,
            str_starts_with($p, 'important') => Priority::Importante,
            default => Priority::Normale,
        };
    }

    private static function waitingOn(?string $text, DossierStatus $status, bool $hasAction): ?WaitingOn
    {
        if (! $hasAction && in_array($status->value, Dossier::closedStatuses(), true)) {
            return null;
        }
        $t = Str::lower(Str::ascii((string) $text));
        if ($t === '') {
            return $status === DossierStatus::EnInstruction ? WaitingOn::Mairie : null;
        }

        return match (true) {
            str_starts_with($t, 'mickael'), str_starts_with($t, 'action interne'), str_starts_with($t, 'aucun tiers') => WaitingOn::Moi,
            str_contains($t, 'mairie') && ! str_contains($t, 'client') => WaitingOn::Mairie,
            str_contains($t, 'spanc') || str_contains($t, 'abf') => WaitingOn::Tiers,
            default => WaitingOn::Client,
        };
    }

    private static function prescriber(?string $p): ?string
    {
        if ($p === null || Str::upper($p) === 'CHOISIR') {
            return null;
        }
        if (preg_match('/^(\w+)\s*\/\s*AKENA$/iu', $p, $m)) {
            return 'AKENA — '.$m[1];
        }

        return $p;
    }

    /** Accepte 2026-09-23, 2026-09-23 10:00:00, 23/09/2026. Une date sans année (16/09) est ignorée. */
    public static function date(?string $v): ?Carbon
    {
        if ($v === null) {
            return null;
        }
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $v, $m)) {
            return Carbon::create((int) $m[1], (int) $m[2], (int) $m[3]);
        }
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $v, $m)) {
            return Carbon::create((int) $m[3], (int) $m[2], (int) $m[1]);
        }

        return null;
    }

    private static function url(?string $v): ?string
    {
        return $v && preg_match('#^https?://\S+$#', $v) && strlen($v) <= 500 ? $v : null;
    }

    /** « PESSAC (33) » → ['Pessac', '33']. */
    private static function splitCity(?string $v): array
    {
        if ($v === null) {
            return [null, null];
        }
        $dept = preg_match('/\((\d{2,3})\)/', $v, $m) ? $m[1] : null;
        $city = trim(preg_replace('/\(\d{2,3}\)/', '', $v));

        return [Str::title(Str::lower($city)), $dept];
    }

    private static function notes(callable $get, ?string $existing): ?string
    {
        $marker = '— Repris de l\'ancien suivi Excel —';
        if ($existing && str_contains($existing, $marker)) {
            $existing = trim(Str::before($existing, $marker));
        }

        $lines = [];
        foreach ([
            'DATE DÉPÔT' => 'Dépôt (historique)', 'NOTES' => 'Notes', 'Référence officielle / projet' => 'Référence',
            'Points à vérifier' => 'Points à vérifier', 'Preuve / contexte' => 'Contexte', 'Dernière action · synthèse' => 'Dernière action',
            'Lien Gmail source' => 'Gmail', 'Carte Trello active' => 'Trello', 'Chemin Drive' => 'Chemin Drive',
            'Observations documentaires' => 'Drive', 'PAYÉ' => 'Payé (montant noté)',
        ] as $col => $label) {
            $v = $get($col);
            if ($v === null || str_starts_with($v, 'Reprise du suivi existant') || $v === 'Dernière action à confirmer'
                || $v === 'Inventaire documentaire ; conformité des pièces non auditée.') {
                continue;
            }
            if ($col === 'DATE DÉPÔT' && self::date($v)) {
                continue;
            }
            $lines[] = "{$label} : {$v}";
        }

        if (! $lines) {
            return $existing ?: null;
        }

        return trim(($existing ? $existing."\n\n" : '').$marker."\n".implode("\n", $lines));
    }
}
