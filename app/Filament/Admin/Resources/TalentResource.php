<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Actions\ModerationActions;
use App\Filament\Admin\Resources\TalentResource\Pages;
use App\Filament\RelationManagers\OffersRelationManager;
use App\Filament\RelationManagers\UnavailabilitiesRelationManager;
use App\Filament\Schemas\ListingForms;
use App\Models\Talent;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TalentResource extends Resource
{
    protected static ?string $model = Talent::class;

    protected static ?string $navigationIcon = 'heroicon-o-musical-note';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string { return __('Contenus'); }

    public static function getModelLabel(): string { return __('fiche culture'); }

    public static function getPluralModelLabel(): string { return __('Culture & artisanat'); }

    public static function getNavigationBadge(): ?string
    {
        $n = Talent::where('status', 'pending')->count();

        return $n ? (string) $n : null;
    }

    public static function form(Form $form): Form
    {
        return $form->schema(ListingForms::talent(admin: true));
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\ImageColumn::make('cover')->label('')->defaultImageUrl(fn ($record) => $record->coverUrl()),
                Tables\Columns\TextColumn::make('name')->label(__('Nom'))->searchable()->sortable()->description(fn ($record) => $record->category.' · '.$record->city),
                Tables\Columns\TextColumn::make('type')->label(__('Rubrique'))->badge()->formatStateUsing(fn ($state) => __(Talent::TYPES[$state]['label'])),
                Tables\Columns\TextColumn::make('owner.name')->label(__('Géré par'))->placeholder(__('Équipe Trôofê')),
                Tables\Columns\TextColumn::make('offers_min_price')->label(__('Dès'))->state(fn ($record) => $record->offers->min('price'))->formatStateUsing(fn ($state) => $state ? money($state) : '—'),
                Tables\Columns\TextColumn::make('bookings_count')->label(__('Demandes'))->counts('bookings')->sortable(),
                Tables\Columns\TextColumn::make('status')->label(__('Statut'))->badge()->formatStateUsing(fn ($state) => __(Talent::statusOptions()[$state] ?? $state))
                    ->color(fn ($state) => match ($state) { 'published' => 'success', 'pending' => 'warning', 'draft' => 'gray', default => 'danger' }),
                Tables\Columns\ToggleColumn::make('is_verified')->label(__('Vérifié')),
                Tables\Columns\ToggleColumn::make('is_featured')->label(__('En avant')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')->label(__('Rubrique'))->options(collect(Talent::TYPES)->map(fn ($t) => __($t['plural']))),
                Tables\Filters\SelectFilter::make('status')->label(__('Statut'))->options(collect(Talent::statusOptions())->map(fn ($l) => __($l))),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([ModerationActions::make(), Tables\Actions\EditAction::make(), Tables\Actions\Action::make('voir')->label(__('Voir'))->icon('heroicon-o-eye')->url(fn ($record) => $record->url())->openUrlInNewTab()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make(), Tables\Actions\RestoreBulkAction::make()])]);
    }

    public static function getRelations(): array
    {
        return [OffersRelationManager::class, UnavailabilitiesRelationManager::class];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('offers')->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTalents::route('/'),
            'create' => Pages\CreateTalent::route('/create'),
            'edit' => Pages\EditTalent::route('/{record}/edit'),
        ];
    }
}
