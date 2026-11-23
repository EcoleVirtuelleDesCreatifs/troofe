<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Actions\ModerationActions;
use App\Filament\Admin\Resources\RestaurantResource\Pages;
use App\Filament\RelationManagers\MenuItemsRelationManager;
use App\Filament\Schemas\ListingForms;
use App\Models\Restaurant;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RestaurantResource extends Resource
{
    protected static ?string $model = Restaurant::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string { return __('Contenus'); }

    public static function getModelLabel(): string { return __('restaurant'); }

    public static function getPluralModelLabel(): string { return __('Restaurants'); }

    public static function getNavigationBadge(): ?string
    {
        $n = Restaurant::where('status', 'pending')->count();

        return $n ? (string) $n : null;
    }

    public static function form(Form $form): Form
    {
        return $form->schema(ListingForms::restaurant(admin: true));
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\ImageColumn::make('cover')->label('')->defaultImageUrl(fn ($record) => $record->coverUrl()),
                Tables\Columns\TextColumn::make('name')->label(__('Nom'))->searchable()->sortable()->description(fn ($record) => $record->cuisine.' · '.$record->city),
                Tables\Columns\TextColumn::make('owner.name')->label(__('Propriétaire'))->placeholder(__('Équipe Trôofê')),
                Tables\Columns\TextColumn::make('status')->label(__('Statut'))->badge()->formatStateUsing(fn ($state) => __(Restaurant::statusOptions()[$state] ?? $state))
                    ->color(fn ($state) => match ($state) { 'published' => 'success', 'pending' => 'warning', 'draft' => 'gray', default => 'danger' }),
                Tables\Columns\IconColumn::make('is_verified')->label(__('Vérifié'))->boolean(),
                Tables\Columns\ToggleColumn::make('is_featured')->label(__('En avant')),
                Tables\Columns\TextColumn::make('views')->label(__('Vues'))->numeric()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label(__('Statut'))->options(collect(Restaurant::statusOptions())->map(fn ($l) => __($l))),
                Tables\Filters\SelectFilter::make('city')->label(__('Ville'))->options(fn () => Restaurant::distinct()->pluck('city', 'city')->all()),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([ModerationActions::make(), Tables\Actions\EditAction::make(), Tables\Actions\Action::make('voir')->label(__('Voir'))->icon('heroicon-o-eye')->url(fn ($record) => route('restaurants.show', $record))->openUrlInNewTab()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make(), Tables\Actions\RestoreBulkAction::make()])]);
    }

    public static function getRelations(): array
    {
        return [MenuItemsRelationManager::class];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRestaurants::route('/'),
            'create' => Pages\CreateRestaurant::route('/create'),
            'edit' => Pages\EditRestaurant::route('/{record}/edit'),
        ];
    }
}
