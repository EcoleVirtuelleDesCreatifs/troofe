<?php

namespace App\Filament\RelationManagers;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TicketTypesRelationManager extends RelationManager
{
    protected static string $relationship = 'ticketTypes';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Billetterie');
    }

    public function form(Form $form): Form
    {
        return $form->columns(2)->schema([
            TextInput::make('name')->label(__('Catégorie de billet'))->required(),
            TextInput::make('price')->label(__('Prix (FCFA, 0 = gratuit)'))->numeric()->required()->minValue(0),
            TextInput::make('quantity')->label(__('Places'))->numeric()->required()->minValue(fn ($record) => $record?->sold ?? 1),
            TextInput::make('max_per_order')->label(__('Maximum par commande'))->numeric()->default(10)->required(),
            TextInput::make('description')->label(__('Description'))->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label(__('Billet')),
            Tables\Columns\TextColumn::make('price')->label(__('Prix'))->formatStateUsing(fn ($state) => $state ? money($state) : __('Gratuit')),
            Tables\Columns\TextColumn::make('sold')->label(__('Vendus'))->formatStateUsing(fn ($state, $record) => "$state / {$record->quantity}"),
            Tables\Columns\TextColumn::make('revenue')->label(__('Chiffre d\'affaires'))->state(fn ($record) => money($record->sold * $record->price)),
        ])
            ->headerActions([Tables\Actions\CreateAction::make()])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()->visible(fn ($record) => $record->sold === 0)]);
    }
}
