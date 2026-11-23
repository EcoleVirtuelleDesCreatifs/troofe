<?php

namespace App\Filament\Schemas;

use App\Models\Event;
use App\Models\Restaurant;
use App\Models\Talent;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;

/** Formulaires communs à l'administration et à l'espace pro */
class ListingForms
{
    /** Onglets FR / EN pour les champs de contenu */
    public static function bilingual(array $fields): Tabs
    {
        $tab = fn (string $label, string $suffix, bool $required) => Tabs\Tab::make($label)->schema(array_map(function (array $f) use ($suffix, $required) {
            [$type, $name, $label] = $f;
            $component = match ($type) {
                'rich' => RichEditor::make($name.$suffix)->toolbarButtons(['bold', 'italic', 'link', 'h2', 'h3', 'bulletList', 'orderedList', 'blockquote']),
                'textarea' => Textarea::make($name.$suffix)->rows(4),
                'tags' => TagsInput::make($name.$suffix),
                default => TextInput::make($name.$suffix)->maxLength(255),
            };

            return $component->label(__($label))->required($required && ($f[3] ?? false));
        }, $fields));

        return Tabs::make('langues')->tabs([
            $tab('Français', '', true),
            $tab('English', '_en', false)->badge(__('facultatif')),
        ])->columnSpanFull();
    }

    public static function moderation(bool $admin): array
    {
        if (! $admin) {
            return [];
        }

        return [Section::make(__('Publication'))->columns(4)->schema([
            Select::make('status')->label(__('Statut'))->options(collect(Restaurant::statusOptions())->map(fn ($l) => __($l)))->required(),
            TextInput::make('moderation_note')->label(__('Note de modération'))->columnSpan(3),
            Toggle::make('is_verified')->label(__('Profil vérifié')),
            Toggle::make('is_featured')->label(__('Mis en avant')),
        ])];
    }

    public static function restaurant(bool $admin = false): array
    {
        return [
            Section::make(__('Identité'))->columns(3)->schema([
                TextInput::make('name')->label(__('Nom'))->required()->maxLength(120)->columnSpan(2),
                TextInput::make('cuisine')->label(__('Cuisine'))->required()->datalist(Restaurant::distinct()->pluck('cuisine')->all()),
                $admin ? Select::make('owner_id')->label(__('Propriétaire'))->relationship('owner', 'name')->searchable()->preload() : Grid::make(1),
                Select::make('price_level')->label(__('Gamme de prix'))->options([1 => 'F', 2 => 'FF', 3 => 'FFF', 4 => 'FFFF'])->default(2)->required(),
                FileUpload::make('cover')->label(__('Photo principale'))->image()->imageEditor()->directory('restaurants')->maxSize(4096)->columnSpanFull(),
                self::bilingual([['text', 'tagline', 'Accroche'], ['textarea', 'description', 'Présentation', true]]),
            ]),
            Section::make(__('Adresse et contact'))->columns(3)->schema([
                TextInput::make('city')->label(__('Ville'))->required(),
                TextInput::make('district')->label(__('Quartier')),
                TextInput::make('country')->label(__('Pays'))->required()->default('Côte d\'Ivoire'),
                TextInput::make('address')->label(__('Adresse'))->columnSpan(2),
                TextInput::make('phone')->label(__('Téléphone'))->tel(),
                TextInput::make('whatsapp')->label('WhatsApp')->helperText(__('Indicatif compris, sans +')),
                TextInput::make('lat')->label(__('Latitude'))->numeric(),
                TextInput::make('lng')->label(__('Longitude'))->numeric(),
                KeyValue::make('opening_hours')->label(__('Horaires'))->keyLabel(__('Jour'))->valueLabel(__('Heures'))->columnSpanFull()
                    ->default(['Lundi' => '11:00–22:00', 'Mardi' => '11:00–22:00', 'Mercredi' => '11:00–22:00', 'Jeudi' => '11:00–22:00', 'Vendredi' => '11:00–23:30', 'Samedi' => '11:00–23:30', 'Dimanche' => '12:00–21:00']),
            ]),
            Section::make(__('Commande en ligne'))->columns(3)->schema([
                Toggle::make('accepts_orders')->label(__('Accepte les commandes'))->default(true),
                Toggle::make('delivery')->label(__('Livraison'))->live(),
                TextInput::make('delivery_fee')->label(__('Frais de livraison (FCFA)'))->numeric()->default(0)->visible(fn (Get $get) => $get('delivery')),
            ]),
            ...self::moderation($admin),
        ];
    }

    public static function talent(bool $admin = false): array
    {
        $craft = fn (Get $get) => $get('type') === 'artisan';

        return [
            Section::make(__('Identité'))->columns(3)->schema([
                Select::make('type')->label(__('Rubrique'))->options(collect(Talent::TYPES)->map(fn ($t) => __($t['label'])))->required()->live()->default('troupe'),
                TextInput::make('name')->label(__('Nom'))->required()->maxLength(120)->columnSpan(2),
                TextInput::make('category')->label(__('Style, discipline ou métier'))->required()->datalist(Talent::distinct()->pluck('category')->all()),
                Select::make('visual')->label(__('Illustration par défaut'))->options(Talent::VISUALS)->helperText(__('Utilisée tant qu\'aucune photo n\'est ajoutée.')),
                $admin ? Select::make('owner_id')->label(__('Propriétaire'))->relationship('owner', 'name')->searchable()->preload() : Grid::make(1),
                FileUpload::make('cover')->label(__('Photo principale'))->image()->imageEditor()->directory('culture')->maxSize(4096)->columnSpanFull(),
                FileUpload::make('gallery')->label(__('Galerie'))->image()->multiple()->reorderable()->maxFiles(8)->directory('culture')->columnSpanFull(),
                self::bilingual([['text', 'tagline', 'Accroche', true], ['textarea', 'description', 'Présentation', true], ['textarea', 'pro_offer', 'Offre aux restaurateurs']]),
            ]),
            Section::make(__('Savoir-faire'))->columns(3)->schema([
                TagsInput::make('tags')->label(__('Styles / disciplines / techniques'))->columnSpan(2),
                TagsInput::make('repertoire')->label(__('Répertoire'))->hidden($craft),
                TagsInput::make('materials')->label(__('Matières'))->visible($craft),
                TextInput::make('maker_name')->label(__('Artisan ou coopérative'))->visible($craft),
                TextInput::make('members')->label(__('Danseurs / artistes'))->numeric()->hidden($craft),
                TextInput::make('musicians')->label(__('Musiciens'))->numeric()->hidden($craft),
                TagsInput::make('languages')->label(__('Langues'))->hidden($craft),
                TextInput::make('since_year')->label(__('En activité depuis'))->numeric()->minValue(1900)->maxValue(date('Y')),
                TagsInput::make('audiences')->label(__('Occasions'))->suggestions(config('troofe.occasions'))->columnSpan(2),
                TagsInput::make('zones')->label(__('Zone d\'intervention')),
                TextInput::make('min_notice_days')->label(__('Délai minimum (jours)'))->numeric()->default(7)->required(),
            ]),
            Section::make(__('Visite d\'atelier'))->columns(3)->visible($craft)->schema([
                TextInput::make('visit_price')->label(__('Prix par personne (FCFA)'))->numeric()->helperText(__('Laisser vide si pas de visite')),
                TextInput::make('visit_duration')->label(__('Durée')),
                TextInput::make('visit_details')->label(__('Déroulé')),
            ]),
            Section::make(__('Localisation et contact'))->columns(3)->schema([
                TextInput::make('city')->label(__('Ville'))->required(),
                TextInput::make('district')->label(__('Quartier')),
                TextInput::make('country')->label(__('Pays'))->required()->default('Côte d\'Ivoire'),
                TextInput::make('phone')->label(__('Téléphone'))->tel()->required(),
                TextInput::make('whatsapp')->label('WhatsApp')->helperText(__('Indicatif compris, sans +')),
            ]),
            ...self::moderation($admin),
        ];
    }

    public static function event(bool $admin = false): array
    {
        return [
            Section::make(__('Événement'))->columns(3)->schema([
                TextInput::make('title')->label(__('Titre'))->required()->maxLength(160)->columnSpan(2),
                TextInput::make('category')->label(__('Catégorie'))->required()->datalist(['Dégustation', 'Festival', 'Dîner-concert', 'Atelier', 'Marché', 'Conférence']),
                FileUpload::make('cover')->label(__('Affiche ou photo'))->image()->imageEditor()->directory('evenements')->maxSize(4096)->columnSpanFull(),
                Tabs::make('langues')->columnSpanFull()->tabs([
                    Tabs\Tab::make('Français')->schema([
                        Textarea::make('excerpt')->label(__('Résumé'))->required()->maxLength(400),
                        RichEditor::make('description')->label(__('Description')),
                    ]),
                    Tabs\Tab::make('English')->badge(__('facultatif'))->schema([
                        TextInput::make('title_en')->label(__('Titre')),
                        Textarea::make('excerpt_en')->label(__('Résumé'))->maxLength(400),
                        RichEditor::make('description_en')->label(__('Description')),
                    ]),
                ]),
            ]),
            Section::make(__('Date et lieu'))->columns(3)->schema([
                DateTimePicker::make('starts_at')->label(__('Début'))->required()->seconds(false)->minDate($admin ? null : now()),
                DateTimePicker::make('ends_at')->label(__('Fin'))->seconds(false)->after('starts_at'),
                TextInput::make('venue')->label(__('Lieu'))->required(),
                TextInput::make('city')->label(__('Ville'))->required(),
                TextInput::make('country')->label(__('Pays'))->required()->default('Côte d\'Ivoire'),
                Select::make('restaurant_id')->label(__('Restaurant partenaire'))->relationship('restaurant', 'name', fn ($query) => $query->published())->searchable()->preload(),
                Select::make('performers')->label(__('Artistes et troupes à l\'affiche'))->relationship('performers', 'name', fn ($query) => $query->published()->whereIn('type', ['troupe', 'artiste']))->multiple()->searchable()->preload()->columnSpanFull(),
                $admin ? Select::make('organizer_id')->label(__('Organisateur'))->relationship('organizer', 'name')->searchable()->preload() : Grid::make(1),
            ]),
            ...($admin ? [Section::make(__('Publication'))->columns(3)->schema([
                Select::make('status')->label(__('Statut'))->options(collect(Event::statusOptions())->map(fn ($l) => __($l)))->required(),
                TextInput::make('moderation_note')->label(__('Note de modération')),
                Toggle::make('is_featured')->label(__('Mis en avant')),
            ])] : []),
        ];
    }

    public static function date(string $name, string $label): DatePicker
    {
        return DatePicker::make($name)->label(__($label))->native(false)->displayFormat('d/m/Y');
    }
}
