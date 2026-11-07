<?php

namespace App\Filament\Resources\Quotes\Pages;

use App\Enums\QuoteStatus;
use App\Filament\Resources\Dossiers\DossierResource;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Resources\Quotes\QuoteResource;
use App\Filament\Support\DeleteActions;
use App\Filament\Support\SendEmailAction;
use App\Filament\Widgets\ClientTrackingWidget;
use App\Models\Quote;
use App\Services\InvoiceService;
use App\Services\QuoteService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditQuote extends EditRecord
{
    protected static string $resource = QuoteResource::class;

    /** false = aperçu du devis tel que le client le verra ; true = formulaire de construction. */
    public bool $editing = false;

    public function mount(int|string $record): void
    {
        parent::mount($record);
        // Devis encore vide : on arrive directement sur le formulaire.
        $this->editing = $this->record->isEditable() && ! $this->record->lines()->exists();
    }

    public function startEditing(): void
    {
        $this->editing = true;
        $this->fillForm();
    }

    public function stopEditing(): void
    {
        $this->editing = false;
    }

    public function getTitle(): string
    {
        return 'Devis '.$this->record->displayNumber().' — '.$this->record->status->label();
    }

    protected function afterSave(): void
    {
        $this->record->recalculate();
        QuoteResource::fillSubjectFromLines($this->record);
        QuoteResource::ensureDossier($this->record);
        $this->editing = false;
    }

    protected function getFormActions(): array
    {
        if (! $this->record->isEditable() || ! $this->editing) {
            return [];
        }

        return [
            $this->getSaveFormAction()->label('Enregistrer et voir l\'aperçu'),
            Action::make('cancelEdit')->label('Annuler')->color('gray')->action(fn () => $this->stopEditing()),
        ];
    }

    protected function getHeaderActions(): array
    {
        /** @var Quote $q */
        $q = $this->record;

        return [
            SendEmailAction::forQuote(
                $q,
                before: fn () => $q->isEditable() && $this->editing ? $this->save(shouldRedirect: false, shouldSendSavedNotification: false) : null,
                after: fn () => $this->redirect(QuoteResource::getUrl('edit', ['record' => $q])),
            ),
            Action::make('pdf')->label('PDF')->icon(Heroicon::OutlinedArrowDownTray)->color('gray')
                ->url(fn () => route('admin.quotes.pdf', $q))->openUrlInNewTab(),
            Action::make('dossier')->label('Dossier')->icon(Heroicon::OutlinedFolder)->color('gray')
                ->visible(fn () => (bool) $q->dossier_id)
                ->url(fn () => DossierResource::getUrl('view', ['record' => $q->dossier_id])),
            ActionGroup::make([
                Action::make('signPaper')->label('Marquer signé (papier)')->icon(Heroicon::OutlinedPencil)
                    ->visible(fn () => $q->status === QuoteStatus::Envoye)
                    ->schema([TextInput::make('signer_name')->label('Nom du signataire')->default($q->client?->display_name)->required()])
                    ->action(function (array $data) use ($q) {
                        QuoteService::sign($q, $data['signer_name'].' (signature papier)', null, null);
                        Notification::make()->title('Devis marqué comme signé')->success()->send();
                        $this->redirect(QuoteResource::getUrl('edit', ['record' => $q]));
                    }),
                Action::make('deposit')->label("Facture d'acompte")->icon(Heroicon::OutlinedBanknotes)
                    ->visible(fn () => $q->status === QuoteStatus::Signe)
                    ->schema([TextInput::make('percent')->label('Pourcentage')->numeric()->suffix('%')->default(30)->required()])
                    ->action(function (array $data) use ($q) {
                        $inv = InvoiceService::createDepositFromQuote($q, (float) $data['percent']);
                        $this->redirect(InvoiceResource::getUrl('edit', ['record' => $inv]));
                    }),
                Action::make('final')->label('Facture finale')->icon(Heroicon::OutlinedReceiptPercent)
                    ->visible(fn () => $q->status === QuoteStatus::Signe)
                    ->action(function () use ($q) {
                        $inv = InvoiceService::createFinalFromQuote($q);
                        $this->redirect(InvoiceResource::getUrl('edit', ['record' => $inv]));
                    }),
                Action::make('refuse')->label('Marquer refusé')->icon(Heroicon::OutlinedXCircle)->color('danger')
                    ->visible(fn () => $q->status === QuoteStatus::Envoye)
                    ->schema([Textarea::make('reason')->label('Motif')])
                    ->action(fn (array $data) => QuoteService::refuse($q, $data['reason'] ?? null)),
                Action::make('duplicate')->label('Dupliquer')->icon(Heroicon::OutlinedDocumentDuplicate)
                    ->action(fn () => $this->redirect(QuoteResource::getUrl('edit', ['record' => QuoteService::duplicate($q)]))),
                DeleteActions::record(QuoteResource::getUrl()),
            ]),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [ClientTrackingWidget::class];
    }
}
