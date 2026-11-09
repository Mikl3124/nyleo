<?php

namespace App\Filament\Resources\Invoices;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Filament\Resources\Clients\ClientResource;
use App\Filament\Resources\Quotes\QuoteResource;
use App\Filament\Support\DeleteActions;
use App\Filament\Support\LineItems;
use App\Models\Dossier;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\InvoiceService;
use App\Support\TrackingSummary;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use UnitEnum;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static string|UnitEnum|null $navigationGroup = 'Facturation';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'facture';

    protected static ?string $navigationLabel = 'Factures et avoirs';

    protected static ?string $recordTitleAttribute = 'number';

    public static function getNavigationBadge(): ?string
    {
        $n = Invoice::whereIn('status', [InvoiceStatus::Emise->value, InvoiceStatus::PartiellementPayee->value])->count();

        return $n ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                Select::make('type')->label('Type')->options([
                    InvoiceType::Facture->value => InvoiceType::Facture->label(),
                    InvoiceType::Acompte->value => InvoiceType::Acompte->label(),
                ])->default(InvoiceType::Facture->value)->required()
                    ->disabled(fn (?Invoice $record) => $record?->isCreditNote()),
                Select::make('client_id')->label('Client')->relationship('client', 'last_name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                    ->searchable(['last_name', 'first_name', 'company_name', 'email'])->preload()->required()->live()
                    ->editOptionForm(fn (Schema $schema) => ClientResource::form($schema))
                    ->editOptionModalHeading('Modifier le client')
                    ->helperText(fn (Get $get) => QuoteResource::clientSummary($get('client_id')))
                    ->afterStateUpdated(fn (Set $set) => $set('dossier_id', null)),
                Select::make('dossier_id')->label('Dossier')
                    ->options(fn (Get $get) => Dossier::where('client_id', $get('client_id'))->get()
                        ->mapWithKeys(fn ($d) => [$d->id => $d->reference.' — '.$d->title])),
                TextInput::make('subject')->label('Objet'),
                DatePicker::make('issue_date')->label("Date d'émission")->helperText('Par défaut : le jour de l\'émission.'),
                DatePicker::make('due_date')->label("Date d'échéance")->helperText('Par défaut : selon le délai des Paramètres.'),
                TextInput::make('deposits_deducted')->label('Acomptes déjà facturés à déduire')->numeric()->suffix('€')->default(0)
                    ->visible(fn (Get $get) => $get('type') === InvoiceType::Facture->value),
                Placeholder::make('total_preview')->label('Total')->content(fn (Get $get) => LineItems::total($get)),
            ]),
            LineItems::repeater(),
            Textarea::make('notes')->label('Mention particulière (affichée sur la facture)')->rows(2)->columnSpanFull(),
        ])->disabled(fn (?Invoice $record) => $record && ! $record->isDraft());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->heading('Factures et avoirs · Nyleo')
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => TrackingSummary::withAggregates($query))
            ->columns([
                // Liste compacte : toutes les colonnes tiennent sur un écran d'ordinateur, sans défiler.
                // « Avoir » passe sous le numéro, le montant réglé sous le montant quand il reste un solde.
                TextColumn::make('number')->label('N°')->placeholder('Brouillon')->searchable()->sortable()
                    ->description(fn (Invoice $r) => $r->isCreditNote() ? 'Avoir' : null),
                TextColumn::make('type')->label('Type')->badge()->formatStateUsing(fn (InvoiceType $state) => $state->label())
                    ->color(fn (InvoiceType $state) => $state === InvoiceType::Avoir ? 'danger' : 'gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('client.last_name')->label('Client')->formatStateUsing(fn ($record) => $record->client?->display_name)->searchable()
                    ->limit(28),
                TextColumn::make('total_ttc')->label('Montant')->formatStateUsing(fn ($state, $record) => ($record->isCreditNote() ? '− ' : '').money($state))->sortable()
                    ->description(fn (Invoice $r) => $r->status === InvoiceStatus::PartiellementPayee ? 'Réglé '.money($r->amount_paid) : null),
                TextColumn::make('amount_paid')->label('Réglé')->formatStateUsing(fn ($state) => money($state))->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')->label('Statut')->badge()
                    ->formatStateUsing(fn (InvoiceStatus $state) => $state->shortLabel())->color(fn (InvoiceStatus $state) => $state->color())
                    ->tooltip(fn (Invoice $r) => $r->status->label()),
                TextColumn::make('issue_date')->label('Émise le')->date('d/m/y')->sortable()->placeholder('—')
                    // L'échéance passe sous la date d'émission, en rouge et en gras quand elle est dépassée.
                    ->description(fn (Invoice $r) => match (true) {
                        ! $r->due_date => null,
                        self::isOverdue($r) => new HtmlString('<span style="color:var(--danger-600);font-weight:700">Échue le '.$r->due_date->format('d/m').'</span>'),
                        default => 'Échéance '.$r->due_date->format('d/m'),
                    }),
                TextColumn::make('due_date')->label('Échéance')->date('d/m/y')->sortable()->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->color(fn (Invoice $r) => self::isOverdue($r) ? 'danger' : null),
                TextColumn::make('reminder_count')->label('Relances')->toggleable(isToggledHiddenByDefault: true),
                TrackingSummary::column(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')->options(InvoiceStatus::options()),
                SelectFilter::make('type')->label('Type')->options(InvoiceType::options()),
                TernaryFilter::make('unpaid')->label('Impayées')
                    ->queries(
                        true: fn (Builder $q) => $q->whereIn('status', [InvoiceStatus::Emise->value, InvoiceStatus::PartiellementPayee->value]),
                        false: fn (Builder $q) => $q->where('status', InvoiceStatus::Payee->value),
                    ),
            ])
            ->recordActions([
                self::paymentAction()->iconButton()->tooltip('Encaisser un paiement'),
                EditAction::make()->label('Ouvrir')->iconButton()->tooltip('Ouvrir'),
                self::deleteAction()->iconButton()->tooltip('Supprimer'),
            ])
            ->toolbarActions([DeleteActions::bulk()]);
    }

    /** Échéance dépassée et pas encore payée en totalité. */
    public static function isOverdue(Invoice $r): bool
    {
        return (bool) $r->due_date?->isPast() && in_array($r->status, [InvoiceStatus::Emise, InvoiceStatus::PartiellementPayee], true);
    }

    public static function paymentAction(): Action
    {
        return Action::make('payment')
            ->label('Encaisser')
            ->icon(Heroicon::OutlinedBanknotes)
            ->color('success')
            ->visible(fn (Invoice $record) => in_array($record->status, [InvoiceStatus::Emise, InvoiceStatus::PartiellementPayee], true))
            ->schema(fn (Invoice $record) => [
                TextInput::make('amount')->label('Montant reçu')->numeric()->suffix('€')->default($record->remaining())->required(),
                Select::make('method')->label('Moyen de paiement')->options(Payment::METHODS)->default('virement')->required(),
                DatePicker::make('paid_at')->label('Date de réception')->default(now())->required(),
                TextInput::make('reference')->label('Référence (n° de chèque, libellé du virement)'),
            ])
            ->action(function (Invoice $record, array $data) {
                InvoiceService::addPayment($record, (float) $data['amount'], $data['method'], $data['paid_at'], $data['reference'] ?? null);
                Notification::make()->title('Paiement enregistré, le client a reçu une confirmation')->success()->send();
            });
    }

    /** Suppression définitive, même d'une facture émise (phase de test), avec paiements et avoirs. */
    public static function deleteAction(): Action
    {
        return Action::make('deleteInvoice')
            ->label('Supprimer')
            ->icon(Heroicon::OutlinedTrash)
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading(fn (Invoice $record) => 'Supprimer '.$record->title().' ?')
            ->modalDescription(function (Invoice $record) {
                if ($record->isDraft()) {
                    return 'Le brouillon sera supprimé définitivement.';
                }
                $linked = [];
                if ($n = $record->payments()->count()) {
                    $linked[] = $n.' paiement(s)';
                }
                if ($n = $record->creditNotes()->count()) {
                    $linked[] = $n.' avoir(s)';
                }

                return 'La facture'.($linked ? ', ses '.implode(' et ses ', $linked) : '').' et son PDF seront supprimés définitivement. '
                    .'À réserver aux essais : une vraie facture émise ne se supprime pas, on l\'annule par un avoir.';
            })
            ->modalSubmitActionLabel('Oui, supprimer')
            ->action(function (Invoice $record) {
                $title = $record->title();
                InvoiceService::delete($record);
                Notification::make()->title($title.' supprimée')->success()->send();
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}
