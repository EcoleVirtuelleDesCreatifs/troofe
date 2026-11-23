<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\DishResource\Pages;
use App\Filament\Schemas\ListingForms;
use App\Models\Dish;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DishResource extends Resource
{
    protected static ?string $model = Dish::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string { return __('Contenus'); }

    public static function getModelLabel(): string { return __('plat'); }

    public static function getPluralModelLabel(): string { return __('Encyclopédie'); }

    public static function form(Form $form): Form
    {
        return $form->columns(3)->schema([
            TextInput::make('name')->label(__('Nom'))->required(),
            TextInput::make('origin_country')->label(__('Pays d\'origine'))->required(),
            TextInput::make('region')->label(__('Région ou peuple')),
            TextInput::make('category')->label(__('Catégorie'))->default('Plat')->datalist(['Plat', 'Sauce', 'Accompagnement', 'Boisson', 'Dessert', 'Street food']),
            TextInput::make('prep_minutes')->label(__('Préparation (minutes)'))->numeric(),
            Select::make('status')->label(__('Statut'))->options(['published' => __('Publié'), 'draft' => __('Brouillon')])->default('published'),
            TagsInput::make('ingredients')->label(__('Ingrédients'))->columnSpanFull(),
            FileUpload::make('image')->label(__('Photo'))->image()->imageEditor()->directory('plats')->columnSpanFull(),
            Select::make('restaurants')->label(__('Restaurants qui le servent'))->relationship('restaurants', 'name')->multiple()->searchable()->preload()->columnSpanFull(),
            Tabs::make('langues')->columnSpanFull()->tabs([
                Tabs\Tab::make('Français')->schema([TextInput::make('summary')->label(__('Résumé'))->required(), RichEditor::make('history')->label(__('Histoire')), RichEditor::make('recipe')->label(__('Recette'))]),
                Tabs\Tab::make('English')->schema([TextInput::make('summary_en')->label(__('Résumé')), RichEditor::make('history_en')->label(__('Histoire')), RichEditor::make('recipe_en')->label(__('Recette'))]),
            ]),
            ListingForms::seo()->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('name')->columns([
            Tables\Columns\ImageColumn::make('image')->label('')->defaultImageUrl(fn ($record) => $record->imageUrl()),
            Tables\Columns\TextColumn::make('name')->label(__('Nom'))->searchable()->sortable()->description(fn ($record) => $record->summary)->wrap(),
            Tables\Columns\TextColumn::make('origin_country')->label(__('Pays'))->sortable(),
            Tables\Columns\IconColumn::make('summary_en')->label('EN')->boolean()->state(fn ($record) => filled($record->summary_en)),
            Tables\Columns\TextColumn::make('views')->label(__('Vues'))->numeric()->sortable(),
            Tables\Columns\TextColumn::make('status')->label(__('Statut'))->badge(),
        ])->filters([Tables\Filters\SelectFilter::make('origin_country')->label(__('Pays'))->options(fn () => Dish::distinct()->pluck('origin_country', 'origin_country')->all())])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageDishes::route('/')];
    }
}
