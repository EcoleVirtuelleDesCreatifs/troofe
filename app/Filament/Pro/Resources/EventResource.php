<?php

namespace App\Filament\Pro\Resources;

use App\Filament\Pro\Resources\EventResource\Pages;
use App\Filament\Schemas\ListingForms;
use App\Models\Event;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    public static function getModelLabel(): string { return __('événement'); }

    public static function getPluralModelLabel(): string { return __('Mes événements'); }

    public static function getNavigationGroup(): ?string { return __('Mes fiches'); }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('organisateur', 'admin') ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('organizer_id', auth()->id());
    }

    public static function form(Form $form): Form
    {
        return $form->schema(ListingForms::event());
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\ImageColumn::make('cover')->label('')->defaultImageUrl(fn ($record) => $record->coverUrl()),
                Tables\Columns\TextColumn::make('title')->label(__('Nom'))->searchable()->weight('bold'),
                Tables\Columns\TextColumn::make('status')->label(__('Statut'))->badge()->formatStateUsing(fn ($state) => __(Event::statusOptions()[$state] ?? $state))
                    ->color(fn ($state) => match ($state) { 'published' => 'success', 'pending' => 'warning', 'draft' => 'gray', default => 'danger' })
                    ->description(fn ($record) => $record->moderation_note && $record->moderation_note !== \App\Services\ListingQuota::NOTE ? $record->moderation_note : ($record->moderation_note ? __('Masquée : limite de la formule') : null)),
                Tables\Columns\TextColumn::make('views')->label(__('Vues'))->numeric(),
                Tables\Columns\TextColumn::make('created_at')->label(__('Créée le'))->date('d/m/Y'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('voir')->label(__('Voir la page'))->icon('heroicon-o-eye')->url(fn ($record) => route('events.show', $record))->openUrlInNewTab(),
            ]);
    }

    public static function getRelations(): array
    {
        return [\App\Filament\RelationManagers\TicketTypesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'edit' => Pages\EditEvent::route('/{record}/edit'),
        ];
    }
}
