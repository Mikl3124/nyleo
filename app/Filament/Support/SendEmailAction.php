<?php

namespace App\Filament\Support;

use App\Enums\DocumentCategory;
use App\Enums\InvoiceType;
use App\Enums\QuoteStatus;
use App\Models\Dossier;
use App\Models\Invoice;
use App\Models\Quote;
use App\Services\DocumentMailer;
use App\Support\EmailTemplates;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

/** Bouton « Envoyer par e-mail » : texte pré-rempli modifiable, PDF joint, lien de signature, pièces jointes. */
class SendEmailAction
{
    public static function forQuote(Quote $quote, ?Closure $before = null, ?Closure $after = null): Action
    {
        $isDraft = $quote->status === QuoteStatus::Brouillon;

        return self::base('sendEmail', $quote->dossier)
            ->label($isDraft ? 'Envoyer au client' : 'Renvoyer par e-mail')
            ->visible(fn () => in_array($quote->status, [QuoteStatus::Brouillon, QuoteStatus::Envoye, QuoteStatus::Signe], true))
            ->modalHeading(($isDraft ? 'Envoyer le devis ' : 'Renvoyer le devis ').$quote->displayNumber())
            ->modalDescription($isDraft ? 'Le devis reçoit son numéro et ne sera plus modifiable.' : null)
            ->fillForm(fn () => self::defaults($quote->client->email, EmailTemplates::forQuote($quote), $quote->dossier, false)
                + ['include_link' => $quote->status !== QuoteStatus::Signe])
            ->action(function (array $data) use ($quote, $before, $after) {
                $before && $before();
                self::run(fn () => DocumentMailer::sendQuote($quote->refresh(), $data), 'Devis envoyé à '.$data['to']);
                $after && $after();
            });
    }

    public static function forInvoice(Invoice $invoice, ?Closure $before = null, ?Closure $after = null): Action
    {
        $isDraft = $invoice->isDraft();

        return self::base('sendEmail', $invoice->dossier)
            ->label($isDraft ? 'Émettre et envoyer' : 'Envoyer par e-mail')
            ->visible(fn () => $invoice->status->value !== 'annulee' || $invoice->isCreditNote())
            ->modalHeading(($isDraft ? 'Émettre et envoyer : ' : 'Envoyer : ').$invoice->title())
            ->modalDescription($isDraft ? 'La facture reçoit son numéro définitif et ne pourra plus être modifiée (seulement annulée par un avoir).' : null)
            ->fillForm(fn () => self::defaults($invoice->client->email, EmailTemplates::forInvoice($invoice), $invoice->dossier,
                $invoice->type === InvoiceType::Facture) + ['include_link' => false])
            ->action(function (array $data) use ($invoice, $before, $after) {
                $before && $before();
                self::run(fn () => DocumentMailer::sendInvoice($invoice->refresh(), $data), $invoice->type->label().' envoyée à '.$data['to']);
                $after && $after();
            });
    }

    private static function base(string $name, ?Dossier $dossier): Action
    {
        $documents = $dossier
            ? $dossier->documents()->latest()->get()->mapWithKeys(fn ($d) => [$d->id => $d->category->label().' — '.$d->displayName()])->all()
            : [];

        return Action::make($name)
            ->icon(Heroicon::OutlinedPaperAirplane)
            ->color('primary')
            ->modalWidth(Width::ThreeExtraLarge)
            ->modalSubmitActionLabel('Envoyer')
            ->modalIcon(Heroicon::OutlinedEnvelope)
            ->schema([
                Grid::make(2)->schema([
                    TextInput::make('to')->label('Destinataire')->email()->required(),
                    TagsInput::make('cc')->label('Copie à')->placeholder('Autre adresse, puis Entrée')
                        ->nestedRecursiveRules(['email']),
                ]),
                TextInput::make('subject')->label('Objet')->required()->maxLength(250),
                Textarea::make('body')->label('Message')->rows(14)->required()
                    ->helperText('Ta signature est ajoutée automatiquement à la fin. Une ligne commençant par « - » devient une puce.'),
                Grid::make(3)->schema([
                    Toggle::make('attach_pdf')->label('Joindre le PDF'),
                    Toggle::make('include_link')->label('Bouton de signature / espace client'),
                    Toggle::make('copy_to_me')->label('M\'envoyer une copie'),
                ]),
                CheckboxList::make('documents')->label('Documents du dossier à joindre')
                    ->options($documents)->visible($documents !== [])->columns(1)->bulkToggleable(),
                FileUpload::make('attachments')->label('Autres pièces jointes')
                    ->multiple()->disk('local')->directory('email-attachments')->visibility('private')
                    ->storeFileNamesIn('attachment_names')->maxSize(15 * 1024)->maxFiles(10)
                    ->helperText('15 Mo maximum par fichier.'),
            ]);
    }

    private static function defaults(?string $to, array $template, ?Dossier $dossier, bool $withLatestPlan): array
    {
        $plan = $withLatestPlan && $dossier
            ? $dossier->documents()->where('category', DocumentCategory::Plan->value)->orderByDesc('version')->value('id')
            : null;

        return [
            'to' => $to,
            'cc' => [],
            'subject' => $template['subject'],
            'body' => $template['body'],
            'attach_pdf' => true,
            'copy_to_me' => true,
            'documents' => $plan ? [$plan] : [],
            'attachments' => [],
        ];
    }

    private static function run(Closure $send, string $success): void
    {
        try {
            $send();
            Notification::make()->title($success)->success()->send();
        } catch (\Throwable $e) {
            report($e);
            Notification::make()->title("L'e-mail n'est pas parti")->body($e->getMessage())->danger()->persistent()->send();
        }
    }
}
