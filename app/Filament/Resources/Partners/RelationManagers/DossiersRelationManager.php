<?php

namespace App\Filament\Resources\Partners\RelationManagers;

use App\Enums\DossierStatus;
use App\Filament\Resources\Dossiers\DossierResource;
use App\Models\Dossier;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/** Dossiers apportés par le partenaire : montant, commissionné ou non, commission, payée ou non. */
class DossiersRelationManager extends RelationManager
{
    protected static string $relationship = 'dossiers';

    protected static ?string $title = 'Dossiers apportés';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['client', 'quotes', 'partner']))
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Aucun dossier pour ce partenaire')
            ->emptyStateDescription('Choisissez ce partenaire sur la fiche d\'un client ou d\'un dossier.')
            ->columns([
                TextColumn::make('reference')->label('Réf.')->searchable(),
                TextColumn::make('title')->label('Projet')->limit(35)->searchable()
                    ->description(fn (Dossier $r) => $r->client?->display_name),
                TextColumn::make('status')->label('Statut')->badge()
                    ->formatStateUsing(fn (DossierStatus $state) => $state->label())->color(fn (DossierStatus $state) => $state->color()),
                TextColumn::make('amount')->label('Montant HT')
                    ->state(fn (Dossier $r) => $r->amountHt())
                    ->formatStateUsing(fn ($state) => money($state))->placeholder('—')
                    ->description(fn (Dossier $r) => $r->amountHt() === null ? 'Pas de devis signé' : null),
                ToggleColumn::make('commissionable')->label('Commissionné'),
                TextColumn::make('commission')->label('Commission')
                    ->state(fn (Dossier $r) => $r->commissionDue())
                    ->formatStateUsing(fn ($state) => money($state))->placeholder('—')
                    ->description(fn (Dossier $r) => $r->isCommissionComputed() && $r->commissionDue() !== null ? 'Calculée' : null),
                ToggleColumn::make('commission_paid')->label('Payée')
                    ->disabled(fn (Dossier $r) => ! $r->commissionable),
                TextColumn::make('commission_paid_at')->label('Payée le')->date('d/m/Y')->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('commission')->label('Commission')
                    ->options(['a_payer' => 'À payer', 'payee' => 'Payée', 'non' => 'Non commissionné'])
                    ->query(fn (Builder $query, array $data) => match ($data['value'] ?? null) {
                        'a_payer' => $query->where('commissionable', true)->where('commission_paid', false),
                        'payee' => $query->where('commissionable', true)->where('commission_paid', true),
                        'non' => $query->where('commissionable', false),
                        default => $query,
                    }),
            ])
            ->recordUrl(fn (Dossier $r) => DossierResource::getUrl('view', ['record' => $r]))
            ->recordActions([
                Action::make('commission')->label('Commission')->icon(Heroicon::OutlinedBanknotes)
                    ->modalHeading(fn (Dossier $r) => 'Commission — '.$r->reference)
                    ->fillForm(fn (Dossier $r) => $r->only(['commissionable', 'commission_amount', 'commission_paid', 'commission_paid_at']))
                    ->schema(self::commissionFields())
                    ->action(function (Dossier $record, array $data) {
                        $record->update($data);
                        Notification::make()->title('Commission enregistrée')->success()->send();
                    }),
            ])
            ->toolbarActions([
                BulkAction::make('markPaid')->label('Marquer les commissions payées')->icon(Heroicon::OutlinedCheckCircle)
                    ->requiresConfirmation()
                    ->schema([DatePicker::make('paid_at')->label('Payées le')->default(today())->required()])
                    ->action(function (Collection $records, array $data) {
                        $records->filter(fn (Dossier $d) => $d->commissionable)
                            ->each(fn (Dossier $d) => $d->update(['commission_paid' => true, 'commission_paid_at' => $data['paid_at']]));
                        Notification::make()->title('Commissions marquées payées')->success()->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ]);
    }

    /** Champs de commission d'un dossier, partagés avec la fiche dossier. */
    public static function commissionFields(): array
    {
        return [
            Toggle::make('commissionable')->label('Ce dossier est commissionné')->default(true)->live(),
            TextInput::make('commission_amount')->label('Montant de la commission')->numeric()->minValue(0)->suffix('€')
                ->helperText('Laisser vide pour la calculer avec la règle du partenaire.')
                ->visible(fn (Get $get) => (bool) $get('commissionable')),
            Toggle::make('commission_paid')->label('Commission payée')->live()
                ->visible(fn (Get $get) => (bool) $get('commissionable')),
            DatePicker::make('commission_paid_at')->label('Payée le')
                ->visible(fn (Get $get) => $get('commissionable') && $get('commission_paid')),
        ];
    }
}
