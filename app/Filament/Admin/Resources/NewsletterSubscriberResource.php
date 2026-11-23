<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\NewsletterSubscriberResource\Pages;
use App\Models\NewsletterSubscriber;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class NewsletterSubscriberResource extends Resource
{
    protected static ?string $model = NewsletterSubscriber::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    public static function getNavigationGroup(): ?string { return __('Abonnements'); }

    public static function getModelLabel(): string { return __('abonné'); }

    public static function getPluralModelLabel(): string { return __('Lettre d\'information'); }

    public static function canCreate(): bool { return false; }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('created_at', 'desc')->columns([
            Tables\Columns\TextColumn::make('email')->searchable()->copyable(),
            Tables\Columns\TextColumn::make('name')->label(__('Nom'))->placeholder('—'),
            Tables\Columns\TextColumn::make('topics')->label(__('Thèmes'))->badge()->formatStateUsing(fn ($state) => __(config("troofe.newsletter_topics.$state", $state))),
            Tables\Columns\TextColumn::make('locale')->label(__('Langue'))->badge(),
            Tables\Columns\TextColumn::make('frequency')->label(__('Fréquence'))->formatStateUsing(fn ($state) => $state === 'monthly' ? __('Mensuelle') : __('Hebdomadaire')),
            Tables\Columns\TextColumn::make('state')->label(__('État'))->badge()->state(fn ($record) => $record->unsubscribed_at ? __('Désinscrit') : ($record->confirmed_at ? __('Confirmé') : __('À confirmer')))
                ->color(fn ($record) => $record->unsubscribed_at ? 'danger' : ($record->confirmed_at ? 'success' : 'warning')),
            Tables\Columns\TextColumn::make('last_sent_at')->label(__('Dernier envoi'))->since()->placeholder('—'),
        ])->filters([
            Tables\Filters\Filter::make('actifs')->label(__('Abonnés actifs'))->query(fn ($query) => $query->active()),
            Tables\Filters\SelectFilter::make('locale')->label(__('Langue'))->options(['fr' => 'Français', 'en' => 'English']),
        ])->headerActions([
            Tables\Actions\Action::make('export')->label(__('Exporter (CSV)'))->icon('heroicon-o-arrow-down-tray')->action(function () {
                return response()->streamDownload(function () {
                    $out = fopen('php://output', 'w');
                    fputcsv($out, ['email', 'name', 'locale', 'topics', 'confirmed_at']);
                    NewsletterSubscriber::active()->each(fn ($state) => fputcsv($out, [$state->email, $state->name, $state->locale, implode('|', $state->topics ?? []), $state->confirmed_at]));
                    fclose($out);
                }, 'abonnes-newsletter.csv');
            }),
        ])->actions([Tables\Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageNewsletterSubscribers::route('/')];
    }
}
