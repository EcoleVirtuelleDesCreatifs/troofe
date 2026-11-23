<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\AdSlotResource\Pages;
use App\Models\AdSlot;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AdSlotResource extends Resource
{
    protected static ?string $model = AdSlot::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    public static function getNavigationGroup(): ?string { return __('Régie publicitaire'); }

    public static function getModelLabel(): string { return __('emplacement'); }

    public static function getPluralModelLabel(): string { return __('Emplacements'); }

    public static function form(Form $form): Form
    {
        return $form->columns(3)->schema([
            TextInput::make('code')->required()->alphaDash()->unique(ignoreRecord: true)->helperText(__('Utilisé dans les gabarits : <x-ad-slot code="…" />')),
            TextInput::make('name')->label(__('Nom'))->required()->columnSpan(2),
            TextInput::make('page')->label(__('Page'))->required(),
            Select::make('format')->options(['leaderboard' => __('Bannière horizontale'), 'native' => __('Carte sponsorisée'), 'rectangle' => __('Encart')])->required(),
            TextInput::make('dimensions')->label(__('Dimensions')),
            TextInput::make('price_per_day')->label(__('Prix par jour (FCFA)'))->numeric()->required(),
            Toggle::make('is_active')->label(__('Actif'))->default(true)->inline(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label(__('Emplacement'))->description(fn ($record) => $record->code),
            Tables\Columns\TextColumn::make('format')->badge(),
            Tables\Columns\TextColumn::make('price_per_day')->label(__('Prix / jour'))->formatStateUsing(fn ($state) => money($state)),
            Tables\Columns\TextColumn::make('live')->label(__('En diffusion'))->state(fn ($record) => $record->campaigns()->live()->count()),
            Tables\Columns\ToggleColumn::make('is_active')->label(__('Actif')),
        ])->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageAdSlots::route('/')];
    }
}
