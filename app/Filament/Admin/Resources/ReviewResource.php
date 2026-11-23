<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ReviewResource\Pages;
use App\Models\Review;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    public static function getNavigationGroup(): ?string { return __('Communauté'); }

    public static function getModelLabel(): string { return __('avis'); }

    public static function getPluralModelLabel(): string { return __('Avis et modération'); }

    public static function canCreate(): bool { return false; }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('status')->label(__('Statut'))->options(['published' => __('Publié'), 'hidden' => __('Masqué')])->required(),
            Textarea::make('body')->label(__('Avis'))->rows(4)->required(),
            Textarea::make('reply')->label(__('Réponse du professionnel'))->rows(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('created_at', 'desc')->columns([
            Tables\Columns\TextColumn::make('user.name')->label(__('Auteur')),
            Tables\Columns\TextColumn::make('reviewable')->label(__('Fiche'))->state(fn ($record) => $record->reviewable?->name ?? $record->reviewable?->title)->description(fn ($record) => $record->reviewable_type),
            Tables\Columns\TextColumn::make('rating')->label(__('Note'))->formatStateUsing(fn ($state) => str_repeat('★', $state))->sortable(),
            Tables\Columns\TextColumn::make('body')->label(__('Avis'))->limit(70)->wrap(),
            Tables\Columns\TextColumn::make('status')->label(__('Statut'))->badge()->color(fn ($state) => $state === 'published' ? 'success' : 'danger'),
            Tables\Columns\TextColumn::make('created_at')->label(__('Date'))->since(),
        ])->filters([Tables\Filters\SelectFilter::make('rating')->label(__('Note'))->options([1 => '★', 2 => '★★', 3 => '★★★', 4 => '★★★★', 5 => '★★★★★'])])
            ->actions([
                Tables\Actions\Action::make('masquer')->label(__('Masquer'))->icon('heroicon-o-eye-slash')->color('danger')->visible(fn ($record) => $record->status === 'published')->requiresConfirmation()->action(fn ($record) => $record->update(['status' => 'hidden'])),
                Tables\Actions\Action::make('publier')->label(__('Republier'))->icon('heroicon-o-eye')->visible(fn ($record) => $record->status !== 'published')->action(fn ($record) => $record->update(['status' => 'published'])),
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageReviews::route('/')];
    }
}
