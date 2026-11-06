<?php

namespace App\Filament\Pages;

use App\Models\CompanySetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Settings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Réglages';

    protected static ?string $navigationLabel = 'Paramètres';

    protected static ?string $title = 'Paramètres de l\'entreprise';

    protected static ?int $navigationSort = 10;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(CompanySetting::current()->attributesToArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Tabs::make()->tabs([
                Tab::make('Entreprise')->icon(Heroicon::OutlinedBuildingOffice)->columns(2)->schema([
                    TextInput::make('trade_name')->label('Nom commercial')->default('Nyleo'),
                    TextInput::make('company_name')->label('Raison sociale / nom de l\'entrepreneur'),
                    TextInput::make('legal_form')->label('Forme juridique')->placeholder('Ex. Entrepreneur individuel (EI), SASU…'),
                    TextInput::make('siret')->label('SIRET'),
                    TextInput::make('rcs')->label('RCS / RNE')->placeholder('Ex. RCS Périgueux 123 456 789'),
                    TextInput::make('ape_code')->label('Code APE'),
                    TextInput::make('address')->label('Adresse')->columnSpanFull(),
                    TextInput::make('postal_code')->label('Code postal'),
                    TextInput::make('city')->label('Commune'),
                    TextInput::make('phone')->label('Téléphone'),
                    TextInput::make('email')->label('E-mail de contact')->email(),
                    TextInput::make('website')->label('Site web'),
                    FileUpload::make('logo_path')->label('Logo (PNG ou JPG)')->disk('public')->directory('logo')->image(),
                ]),
                Tab::make('Paiement')->icon(Heroicon::OutlinedBanknotes)->columns(2)->schema([
                    TextInput::make('bank_name')->label('Banque'),
                    TextInput::make('iban')->label('IBAN'),
                    TextInput::make('bic')->label('BIC'),
                    TextInput::make('cheque_order')->label('Ordre des chèques'),
                    TextInput::make('cheque_address')->label('Adresse d\'envoi des chèques')->columnSpanFull(),
                    TextInput::make('payment_terms_days')->label('Délai de paiement (jours)')->numeric()->required(),
                    TextInput::make('quote_validity_days')->label('Validité des devis (jours)')->numeric()->required(),
                    Textarea::make('payment_terms_text')->label('Conditions de paiement')->rows(2)->columnSpanFull(),
                    Textarea::make('late_penalty_text')->label('Pénalités de retard (mention obligatoire)')->rows(3)->columnSpanFull(),
                ]),
                Tab::make('TVA et mentions')->icon(Heroicon::OutlinedScale)->columns(2)->schema([
                    Toggle::make('vat_enabled')->label('Je facture la TVA')->live()->columnSpanFull(),
                    TextInput::make('vat_number')->label('N° de TVA intracommunautaire')->visible(fn (Get $get) => $get('vat_enabled')),
                    TextInput::make('default_vat_rate')->label('Taux par défaut')->numeric()->suffix('%')->visible(fn (Get $get) => $get('vat_enabled')),
                    TextInput::make('vat_exemption_mention')->label('Mention d\'exonération')->visible(fn (Get $get) => ! $get('vat_enabled'))->columnSpanFull(),
                    Textarea::make('quote_footer')->label('Texte en bas des devis')->rows(3)->columnSpanFull(),
                    Textarea::make('invoice_footer')->label('Texte en bas des factures')->rows(3)->columnSpanFull(),
                ]),
                Tab::make('Numérotation')->icon(Heroicon::OutlinedHashtag)->columns(3)->schema([
                    TextInput::make('quote_prefix')->label('Préfixe devis')->required(),
                    TextInput::make('invoice_prefix')->label('Préfixe factures')->required(),
                    TextInput::make('credit_note_prefix')->label('Préfixe avoirs')->required(),
                    TextInput::make('quote_next_number')->label('Prochain n° de devis')->numeric()->required()
                        ->helperText('Pour continuer la suite d\'Indy, mets le dernier numéro + 1.'),
                    TextInput::make('invoice_next_number')->label('Prochain n° de facture')->numeric()->required(),
                    TextInput::make('credit_note_next_number')->label('Prochain n° d\'avoir')->numeric()->required(),
                ]),
                Tab::make('Automatisations')->icon(Heroicon::OutlinedBolt)->columns(2)->schema([
                    Toggle::make('auto_reminders')->label('Relancer automatiquement les factures impayées et les devis non signés')->columnSpanFull(),
                    TextInput::make('reminder_first_days')->label('1re relance facture, jours après l\'échéance')->numeric()->required(),
                    TextInput::make('reminder_interval_days')->label('Jours entre deux relances')->numeric()->required(),
                    TextInput::make('reminder_max')->label('Nombre maximum de relances')->numeric()->required(),
                    TextInput::make('quote_reminder_days')->label('Relance devis non signé après (jours)')->numeric()->required(),
                    Textarea::make('email_signature')->label('Signature des e-mails')->rows(3)->columnSpanFull()
                        ->placeholder("Mickaël Delpech\n07 85 65 91 65"),
                ]),
            ])->columnSpanFull(),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([Action::make('save')->label('Enregistrer')->submit('save')]),
                ]),
        ]);
    }

    public function save(): void
    {
        CompanySetting::current()->update($this->form->getState());
        Notification::make()->title('Paramètres enregistrés')->success()->send();
    }
}
