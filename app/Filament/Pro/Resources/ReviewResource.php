<?php

namespace App\Filament\Pro\Resources;

use App\Filament\Pro\Resources\ReviewResource\Pages;
use App\Models\Event;
use App\Models\Restaurant;
use App\Models\Review;
use App\Models\Talent;
use App\Notifications\Engagement\ReviewReplied;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?string $slug = 'reviews';

    public static function getNavigationGroup(): ?string { return __('Activité'); }

    public static function getModelLabel(): string { return __('avis'); }

    public static function getPluralModelLabel(): string { return __('Avis reçus'); }

    public static function canCreate(): bool { return false; }

    public static function getEloquentQuery(): Builder
    {
        $uid = auth()->id();

        return parent::getEloquentQuery()->where('status', 'published')->where(fn ($query) => $query
            ->whereHasMorph('reviewable', [Restaurant::class, Talent::class], fn ($m) => $m->where('owner_id', $uid))
            ->orWhereHasMorph('reviewable', [Event::class], fn ($m) => $m->where('organizer_id', $uid)));
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('created_at', 'desc')->columns([
            Tables\Columns\TextColumn::make('reviewable')->label(__('Fiche'))->state(fn ($record) => $record->reviewable?->name ?? $record->reviewable?->title),
            Tables\Columns\TextColumn::make('user.name')->label(__('Client')),
            Tables\Columns\TextColumn::make('rating')->label(__('Note'))->formatStateUsing(fn ($state) => str_repeat('★', $state)),
            Tables\Columns\TextColumn::make('body')->label(__('Avis'))->wrap()->limit(120),
            Tables\Columns\IconColumn::make('reply')->label(__('Répondu'))->boolean()->state(fn ($record) => filled($record->reply)),
        ])->actions([
            Tables\Actions\Action::make('repondre')->label(fn ($record) => $record->reply ? __('Modifier ma réponse') : __('Répondre'))->icon('heroicon-o-chat-bubble-left')
                ->fillForm(fn ($record) => ['reply' => $record->reply])
                ->form([Textarea::make('reply')->label(__('Votre réponse publique'))->required()->rows(4)->maxLength(1500)])
                ->action(function (Review $record, array $data) {
                    $record->update(['reply' => $data['reply'], 'replied_at' => now()]);
                    $m = $record->reviewable;
                    $url = $m instanceof Restaurant ? route('restaurants.show', $m) : ($m instanceof Event ? route('events.show', $m) : $m->url());
                    $record->user->notify(new ReviewReplied($record, $m->name ?? $m->title, $url));
                }),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageReviews::route('/')];
    }
}
