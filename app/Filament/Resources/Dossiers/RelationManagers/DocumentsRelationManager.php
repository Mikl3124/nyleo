<?php

namespace App\Filament\Resources\Dossiers\RelationManagers;

use App\Enums\DocumentCategory;
use App\Models\Document;
use App\Services\DossierWorkflow;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $title = 'Documents';

    public function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Select::make('category')->label('Type')->options(DocumentCategory::options())->default('plan')->required(),
            TextInput::make('name')->label('Nom affiché')->required()->placeholder('Ex. Plans DP véranda'),
            FileUpload::make('path')->label('Fichier')->disk('local')
                ->directory(fn () => 'dossiers/'.$this->getOwnerRecord()->id)
                ->preserveFilenames()->maxSize(51200)->required()->columnSpanFull()
                ->storeFileNamesIn('original_name'),
            Toggle::make('visible_to_client')->label('Visible dans l\'espace client')->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label('Document')->formatStateUsing(fn (Document $r) => $r->displayName())
                    ->description(fn (Document $r) => $r->client_feedback ? '« '.$r->client_feedback.' »' : null),
                TextColumn::make('category')->label('Type')->badge()->formatStateUsing(fn (DocumentCategory $state) => $state->label()),
                TextColumn::make('uploaded_by')->label('Ajouté par')->formatStateUsing(fn ($state) => $state === 'client' ? 'Client' : 'Toi'),
                IconColumn::make('visible_to_client')->label('Visible client')->boolean(),
                TextColumn::make('validation')->label('Validation')->state(fn (Document $r) => match (true) {
                    (bool) $r->validated_at => 'Validé le '.$r->validated_at->format('d/m'),
                    (bool) $r->change_requested_at => 'Modification demandée',
                    $r->requires_validation => 'En attente du client',
                    default => null,
                })->badge()->color(fn (Document $r) => $r->validated_at ? 'success' : ($r->change_requested_at ? 'danger' : 'warning')),
                TextColumn::make('created_at')->label('Date')->dateTime('d/m/Y H:i'),
            ])
            ->headerActions([
                CreateAction::make()->label('Ajouter un document')
                    ->mutateDataUsing(fn (array $data) => $data + ['uploaded_by' => 'admin'])
                    ->after(fn (Document $record) => $record->dossier->log('document', "Document ajouté : {$record->displayName()}", $record->visible_to_client)),
            ])
            ->recordActions([
                Action::make('submit')->label('Envoyer pour validation')->icon(Heroicon::OutlinedPaperAirplane)->color('primary')
                    ->visible(fn (Document $r) => $r->category === DocumentCategory::Plan && ! $r->validated_at && ! $r->isAwaitingValidation())
                    ->schema([Textarea::make('message')->label('Message au client (facultatif)')->rows(2)])
                    ->modalDescription('Le client reçoit un e-mail et pourra valider les plans ou demander une modification en un clic. À la validation, la facture part automatiquement.')
                    ->action(function (Document $record, array $data) {
                        DossierWorkflow::submitPlans($record, $data['message'] ?? null);
                        Notification::make()->title('Plans envoyés au client')->success()->send();
                    }),
                Action::make('download')->label('Télécharger')->icon(Heroicon::OutlinedArrowDownTray)->color('gray')
                    ->url(fn (Document $r) => route('admin.documents.download', $r))->openUrlInNewTab(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
