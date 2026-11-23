<?php

namespace App\Filament\RelationManagers;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class UnavailabilitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'unavailabilities';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->type !== 'artisan';
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Dates indisponibles');
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            DatePicker::make('date')->label(__('Date'))->required()->native(false)->minDate(today())
                ->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule) => $rule->where('talent_id', $this->getOwnerRecord()->id)),
            TextInput::make('note')->label(__('Note interne')),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->defaultSort('date')
            ->modifyQueryUsing(fn ($query) => $query->where('date', '>=', today()))
            ->columns([
                Tables\Columns\TextColumn::make('date')->label(__('Date'))->formatStateUsing(fn ($state) => date_human($state)),
                Tables\Columns\TextColumn::make('note')->label(__('Note')),
            ])
            ->headerActions([Tables\Actions\CreateAction::make()->label(__('Bloquer une date'))])
            ->actions([Tables\Actions\DeleteAction::make()->label(__('Libérer'))]);
    }
}
