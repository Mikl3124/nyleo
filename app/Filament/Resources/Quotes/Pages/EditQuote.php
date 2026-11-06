<?php

namespace App\Filament\Resources\Quotes\Pages;

use App\Enums\QuoteStatus;
use App\Filament\Resources\Dossiers\DossierResource;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Resources\Quotes\QuoteResource;
use App\Models\Quote;
use App\Services\InvoiceService;
use App\Services\QuoteService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditQuote extends EditRecord
{
    protected static string $resource = QuoteResource::class;

    public function getTitle(): string
    {
        return 'Devis '.$this->record->displayNumber().' — '.$this->record->status->label();
    }

    protected function afterSave(): void
    {
        $this->record->recalculate();
        QuoteResource::ensureDossier($this->record);
    }

    protected function getFormActions(): array
    {
        return $this->record->isEditable() ? parent::getFormActions() : [];
    }

    protected function getHeaderActions(): array
    {
        /** @var Quote $q */
        $q = $this->record;

        return [
            Action::make('send')
                ->label($q->status === QuoteStatus::Envoye ? 'Renvoyer au client' : 'Envoyer au client')
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->color('primary')
                ->visible(fn () => in_array($q->status, [QuoteStatus::Brouillon, QuoteStatus::Envoye], true))
                ->requiresConfirmation()
                ->modalDescription('Le client reçoit un e-mail avec un lien pour lire et signer le devis en ligne. Le devis ne sera plus modifiable.')
                ->action(function () use ($q) {
                    if ($q->isEditable()) {
                        $this->save(shouldRedirect: false, shouldSendSavedNotification: false);
                    }
                    try {
                        QuoteService::send($q->refresh());
                        Notification::make()->title('Devis envoyé à '.$q->client->email)->success()->send();
                    } catch (\Throwable $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                    $this->redirect(QuoteResource::getUrl('edit', ['record' => $q]));
                }),
            Action::make('pdf')->label('PDF')->icon(Heroicon::OutlinedArrowDownTray)->color('gray')
                ->url(fn () => route('admin.quotes.pdf', $q))->openUrlInNewTab(),
            Action::make('dossier')->label('Dossier')->icon(Heroicon::OutlinedFolder)->color('gray')
                ->visible(fn () => (bool) $q->dossier_id)
                ->url(fn () => DossierResource::getUrl('view', ['record' => $q->dossier_id])),
            ActionGroup::make([
                Action::make('signPaper')->label('Marquer signé (papier)')->icon(Heroicon::OutlinedPencil)
                    ->visible(fn () => $q->status === QuoteStatus::Envoye)
                    ->schema([TextInput::make('signer_name')->label('Nom du signataire')->default($q->client->display_name)->required()])
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
                DeleteAction::make()->visible(fn () => $q->isEditable()),
            ]),
        ];
    }
}
