<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Actions\ModerationActions;
use App\Filament\Admin\Resources\EventResource\Pages;
use App\Filament\RelationManagers\TicketTypesRelationManager;
use App\Filament\Schemas\ListingForms;
use App\Models\Event;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string { return __('Contenus'); }

    public static function getModelLabel(): string { return __('événement'); }

    public static function getPluralModelLabel(): string { return __('Événements'); }

    public static function getNavigationBadge(): ?string
    {
        $n = Event::where('status', 'pending')->count();

        return $n ? (string) $n : null;
    }

    public static function form(Form $form): Form
    {
        return $form->schema(ListingForms::event(admin: true));
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('starts_at', 'desc')
            ->columns([
                Tables\Columns\ImageColumn::make('cover')->label('')->defaultImageUrl(fn ($record) => $record->coverUrl()),
                Tables\Columns\TextColumn::make('title')->label(__('Titre'))->searchable()->description(fn ($record) => $record->venue.', '.$record->city)->limit(50),
                Tables\Columns\TextColumn::make('starts_at')->label(__('Date'))->dateTime('d/m/Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('organizer.name')->label(__('Organisateur'))->placeholder('—'),
                Tables\Columns\TextColumn::make('sold')->label(__('Billets'))->state(fn ($record) => $record->ticketTypes->sum('sold').' / '.$record->ticketTypes->sum('quantity')),
                Tables\Columns\TextColumn::make('status')->label(__('Statut'))->badge()->formatStateUsing(fn ($state) => __(Event::statusOptions()[$state] ?? $state))
                    ->color(fn ($state) => match ($state) { 'published' => 'success', 'pending' => 'warning', 'draft' => 'gray', default => 'danger' }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label(__('Statut'))->options(collect(Event::statusOptions())->map(fn ($l) => __($l))),
                Tables\Filters\Filter::make('a_venir')->label(__('À venir'))->query(fn ($query) => $query->where('starts_at', '>=', now()))->default(),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([ModerationActions::make(), Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getRelations(): array
    {
        return [TicketTypesRelationManager::class];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('ticketTypes')->withoutGlobalScopes([SoftDeletingScope::class]);
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
