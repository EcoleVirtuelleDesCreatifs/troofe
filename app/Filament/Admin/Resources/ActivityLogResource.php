<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ActivityLogResource\Pages;
use App\Models\ActivityLog;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ActivityLogResource extends Resource
{
    protected static ?string $model = ActivityLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?int $navigationSort = 99;

    public static function getNavigationGroup(): ?string { return __('Système'); }

    public static function getModelLabel(): string { return __('entrée'); }

    public static function getPluralModelLabel(): string { return __('Journal d\'activité'); }

    public static function canCreate(): bool { return false; }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('created_at', 'desc')->columns([
            Tables\Columns\TextColumn::make('created_at')->label(__('Date'))->dateTime('d/m/Y H:i')->sortable(),
            Tables\Columns\TextColumn::make('user.name')->label(__('Par'))->placeholder(__('Système')),
            Tables\Columns\TextColumn::make('action')->label(__('Action'))->searchable(),
            Tables\Columns\TextColumn::make('subject_type')->label(__('Objet'))->description(fn ($record) => '#'.$record->subject_id),
            Tables\Columns\TextColumn::make('details')->label(__('Détails'))->limit(80)->wrap(),
            Tables\Columns\TextColumn::make('ip')->label('IP')->toggleable(isToggledHiddenByDefault: true),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageActivityLogs::route('/')];
    }
}
