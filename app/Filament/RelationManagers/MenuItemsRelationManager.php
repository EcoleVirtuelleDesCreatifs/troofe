<?php

namespace App\Filament\RelationManagers;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class MenuItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'menuItems';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Carte');
    }

    public function form(Form $form): Form
    {
        return $form->columns(3)->schema([
            TextInput::make('category')->label(__('Rubrique'))->required()->default('Plats')->datalist(['Entrées', 'Plats', 'Grillades', 'Accompagnements', 'Desserts', 'Boissons']),
            TextInput::make('price')->label(__('Prix (FCFA)'))->numeric()->required()->minValue(0),
            Toggle::make('is_available')->label(__('Disponible'))->default(true)->inline(false),
            Tabs::make('langues')->columnSpanFull()->tabs([
                Tabs\Tab::make('Français')->schema([TextInput::make('name')->label(__('Nom'))->required(), Textarea::make('description')->label(__('Description'))->rows(2)]),
                Tabs\Tab::make('English')->schema([TextInput::make('name_en')->label(__('Nom')), Textarea::make('description_en')->label(__('Description'))->rows(2)]),
            ]),
            FileUpload::make('image')->label(__('Photo'))->image()->directory('menus')->maxSize(2048)->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->reorderable('position')->defaultSort('category')
            ->columns([
                Tables\Columns\ImageColumn::make('image')->label('')->square(),
                Tables\Columns\TextColumn::make('name')->label(__('Nom'))->searchable()->description(fn ($record) => $record->name_en),
                Tables\Columns\TextColumn::make('category')->label(__('Rubrique'))->badge(),
                Tables\Columns\TextColumn::make('price')->label(__('Prix'))->formatStateUsing(fn ($state) => money($state))->sortable(),
                Tables\Columns\ToggleColumn::make('is_available')->label(__('Disponible')),
            ])
            ->headerActions([Tables\Actions\CreateAction::make()])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }
}
