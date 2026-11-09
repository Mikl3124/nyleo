<?php

namespace App\Filament\Pages;

use App\Models\ReceiptEntry;
use App\Services\Billing\ReceiptLedger;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use UnitEnum;

class Accounting extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calculator';

    protected static string|UnitEnum|null $navigationGroup = 'Facturation';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Comptabilité · URSSAF';

    protected string $view = 'filament.pages.accounting';

    public array $classifications = [];

    public string $month = '';

    public string $category = '';

    public bool $otherReceiptsComplete = false;

    public array $receipt = ['date' => '', 'customer' => '', 'document' => '', 'amount' => '', 'method' => 'Virement', 'reference' => '', 'category' => '', 'note' => ''];

    #[Locked]
    public string $entryToken = '';

    public function mount(): void
    {
        $this->month = now('Europe/Paris')->subMonthNoOverflow()->format('Y-m');
        $this->receipt['date'] = now('Europe/Paris')->toDateString();
        $this->entryToken = (string) Str::uuid();
        $p = DB::table('accounting_profiles')->first();
        $this->category = $p?->category ?? '';
    }

    public function updatedClassifications($value, $key): void
    {
        abort_unless(ReceiptEntry::whereKey($key)->exists(), 404);
        validator(['category' => $value], ['category' => 'required|in:bic_services,bnc,ventes'])->validate();
        DB::table('receipt_classifications')->updateOrInsert(['receipt_entry_id' => $key], ['category' => $value, 'created_at' => now(), 'updated_at' => now()]);
    }

    public function updatedMonth(): void
    {
        $this->otherReceiptsComplete = false;
    }

    public function saveCategory(): void
    {
        $this->validate(['category' => 'nullable|in:bic_services,bnc,ventes']);
        DB::table('accounting_profiles')->updateOrInsert(['id' => 1], ['frequency' => 'monthly', 'category' => $this->category ?: null, 'has_other_receipts' => true, 'created_at' => now(), 'updated_at' => now()]);
        Notification::make()->title('Rubrique enregistrée')->success()->send();
    }

    public function addReceipt(): void
    {
        $this->validate(['receipt.date' => 'required|date|before_or_equal:today', 'receipt.customer' => 'required|string|max:255', 'receipt.document' => 'required|string|max:255', 'receipt.amount' => 'required|numeric|min:0.01|max:99999999', 'receipt.method' => 'required|in:Virement,Chèque,Espèces,Carte,Autre', 'receipt.reference' => 'nullable|string|max:255', 'receipt.category' => 'nullable|in:bic_services,bnc,ventes', 'receipt.note' => 'nullable|string|max:1000']);
        app(ReceiptLedger::class)->manual($this->receipt, $this->entryToken);
        $this->entryToken = (string) Str::uuid();
        $this->receipt = array_replace($this->receipt, ['amount' => '', 'customer' => '', 'document' => '', 'reference' => '', 'note' => '']);
        $this->otherReceiptsComplete = false;
        Notification::make()->title('Recette enregistrée dans le livre')->success()->send();
    }

    public function summary(): array
    {
        $this->validate(['month' => 'required|date_format:Y-m']);
        $ledger = app(ReceiptLedger::class);
        $ledger->sync();
        $summary = $ledger->period($this->month);
        $summary['categories'] = $summary['rows']->groupBy(fn ($r) => $r->declaration_category ?: ($r->source === 'Autres recettes' ? 'unclassified' : ($this->category ?: 'unclassified')))->map(fn ($rows) => $rows->sum(fn ($r) => $r->gross_minor - $r->vat_minor))->all();

        return $summary;
    }

    public function archivePreparation(): void
    {
        $s = $this->summary();
        $this->validate(['otherReceiptsComplete' => 'accepted']);
        if (isset($s['categories']['unclassified']) || $s['alerts']) {
            Notification::make()->title('Classez les recettes et résolvez les contrôles avant validation')->warning()->send();

            return;
        }
        $snapshot = ['period' => $this->month, 'base_minor' => $s['base_minor'], 'categories' => $s['categories'], 'receipt_ids' => $s['rows']->pluck('id')->all(), 'other_receipts_complete' => true, 'prepared_by' => auth()->id(), 'prepared_at' => now()->toIso8601String(), 'status' => 'prepared_not_filed'];
        DB::table('accounting_preparations')->insert(['period' => $this->month, 'snapshot' => json_encode($snapshot), 'content_hash' => hash('sha256', json_encode($snapshot)), 'created_at' => now()]);
        Notification::make()->title('Préparation archivée — déclaration à effectuer sur URSSAF')->success()->send();
    }

    public function exportReceipts()
    {
        $s = $this->summary();

        return response()->streamDownload(function () use ($s) {
            $f = fopen('php://output', 'w');
            fwrite($f, "\xEF\xBB\xBF");
            fputcsv($f, ['Date encaissement', 'Origine', 'Client', 'Pièce', 'Mode', 'Référence', 'Encaissé brut EUR', 'TVA EUR', 'Base HT avant frais EUR', 'Rubrique', 'ID écriture'], ';', '"', '');
            foreach ($s['rows'] as $r) {
                $row = [$r->received_on->format('Y-m-d'), $r->source, $r->customer, $r->document, $r->method, $r->reference, number_format($r->gross_minor / 100, 2, '.', ''), number_format($r->vat_minor / 100, 2, '.', ''), number_format(($r->gross_minor - $r->vat_minor) / 100, 2, '.', ''), $r->declaration_category ?: ($r->source === 'Autres recettes' ? 'À confirmer' : ($this->category ?: 'À confirmer')), $r->id];
                $row = array_map(fn ($v) => is_string($v) && ! is_numeric($v) && preg_match('/^[\s]*[=+@-]/u', $v) ? "'".$v : $v, $row);
                fputcsv($f, $row, ';', '"', '');
            }fclose($f);
        }, 'livre-recettes-'.$this->month.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
