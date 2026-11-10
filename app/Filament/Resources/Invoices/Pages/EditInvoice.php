<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Enums\InvoiceStatus;
use App\Filament\Resources\Dossiers\DossierResource;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Support\SendEmailAction;
use App\Filament\Widgets\ClientTrackingWidget;
use App\Models\Invoice;
use App\Services\CardPayment;
use App\Services\InvoiceService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    public function getTitle(): string
    {
        return $this->record->title().' — '.$this->record->status->label();
    }

    protected function afterSave(): void
    {
        $this->record->recalculate();
    }

    protected function getFormActions(): array
    {
        return $this->record->isDraft() ? parent::getFormActions() : [];
    }

    protected function getHeaderActions(): array
    {
        /** @var Invoice $inv */
        $inv = $this->record;

        return [
            SendEmailAction::forInvoice(
                $inv,
                before: fn () => $inv->isDraft() ? $this->save(shouldRedirect: false, shouldSendSavedNotification: false) : null,
                after: fn () => $this->redirect(InvoiceResource::getUrl('edit', ['record' => $inv])),
            ),
            InvoiceResource::paymentAction()->record($inv)->after(fn () => $this->redirect(InvoiceResource::getUrl('edit', ['record' => $inv]))),
            Action::make('pdf')->label('PDF')->icon(Heroicon::OutlinedArrowDownTray)->color('gray')
                ->url(fn () => route('admin.invoices.pdf', $inv))->openUrlInNewTab(),
            ActionGroup::make([
                Action::make('issue')->label('Émettre sans envoyer')->icon(Heroicon::OutlinedCheckBadge)
                    ->visible(fn () => $inv->isDraft())
                    ->requiresConfirmation()
                    ->modalDescription('La facture reçoit son numéro définitif et ne pourra plus être modifiée (seulement annulée par un avoir). Aucun e-mail n\'est envoyé.')
                    ->action(function () use ($inv) {
                        $this->save(shouldRedirect: false, shouldSendSavedNotification: false);
                        try {
                            InvoiceService::issue($inv->refresh(), notify: false);
                            Notification::make()->title($inv->title().' émise')->success()->send();
                        } catch (\Throwable $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                        $this->redirect(InvoiceResource::getUrl('edit', ['record' => $inv]));
                    }),
                Action::make('cardPayment')
                    ->label(fn () => $inv->card_payment ? 'Retirer le paiement par carte' : 'Proposer le paiement par carte')
                    ->icon(Heroicon::OutlinedCreditCard)
                    ->visible(fn () => ! $inv->isDraft() && in_array($inv->status, [InvoiceStatus::Emise, InvoiceStatus::PartiellementPayee], true))
                    ->action(function () use ($inv) {
                        $inv->forceFill(['card_payment' => ! $inv->card_payment])->saveQuietly();
                        Notification::make()->title($inv->card_payment ? 'Le client peut maintenant payer par carte' : 'Paiement par carte retiré')
                            ->body($inv->card_payment && ! CardPayment::enabled() ? 'Stripe n\'est pas encore configuré : le bouton apparaîtra une fois les clés ajoutées.' : null)
                            ->success()->send();
                        $this->redirect(InvoiceResource::getUrl('edit', ['record' => $inv]));
                    }),
                Action::make('remind')->label('Relancer maintenant')->icon(Heroicon::OutlinedBellAlert)
                    ->visible(fn () => in_array($inv->status, [InvoiceStatus::Emise, InvoiceStatus::PartiellementPayee], true) && $inv->client->email)
                    ->requiresConfirmation()
                    ->action(function () use ($inv) {
                        InvoiceService::remind($inv);
                        Notification::make()->title('Relance envoyée')->success()->send();
                    }),
                Action::make('creditNote')->label('Créer un avoir')->icon(Heroicon::OutlinedArrowUturnLeft)->color('danger')
                    ->visible(fn () => ! $inv->isDraft() && ! $inv->isCreditNote() && $inv->status !== InvoiceStatus::Annulee)
                    ->schema([
                        TextInput::make('amount')->label('Montant de l\'avoir (vide = avoir total)')->numeric()->suffix('€'),
                        TextInput::make('reason')->label('Motif'),
                    ])
                    ->action(function (array $data) use ($inv) {
                        $credit = InvoiceService::createCreditNote($inv, filled($data['amount']) ? (float) $data['amount'] : null, $data['reason'] ?? null);
                        $this->redirect(InvoiceResource::getUrl('edit', ['record' => $credit]));
                    }),
                Action::make('dossier')->label('Voir le dossier')->icon(Heroicon::OutlinedFolder)
                    ->visible(fn () => (bool) $inv->dossier_id)
                    ->url(fn () => DossierResource::getUrl('view', ['record' => $inv->dossier_id])),
                InvoiceResource::deleteAction()->record($inv)->after(fn () => $this->redirect(InvoiceResource::getUrl('index'))),
            ]),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [ClientTrackingWidget::class];
    }
}
