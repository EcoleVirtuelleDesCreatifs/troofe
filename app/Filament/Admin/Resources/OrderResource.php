<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\OrderResource\Pages;
use App\Models\Order;
use App\Services\OrderService;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    public static function getNavigationGroup(): ?string { return __('Commerce'); }

    public static function getModelLabel(): string { return __('commande'); }

    public static function getPluralModelLabel(): string { return __('Commandes et billets'); }

    public static function canCreate(): bool { return false; }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->columns(3)->schema([
            TextEntry::make('reference')->label(__('Référence')),
            TextEntry::make('user.name')->label(__('Client')),
            TextEntry::make('contact_phone')->label(__('Téléphone')),
            TextEntry::make('status')->label(__('Statut'))->formatStateUsing(fn ($state, $record) => __($record->statusLabel())),
            TextEntry::make('total')->label(__('Total'))->formatStateUsing(fn ($state) => money($state)),
            TextEntry::make('commission')->label(__('Commission'))->formatStateUsing(fn ($state) => money($state)),
            TextEntry::make('delivery_address')->label(__('Adresse'))->placeholder('—')->columnSpan(2),
            TextEntry::make('note')->label(__('Note'))->placeholder('—'),
            RepeatableEntry::make('items')->label(__('Articles'))->columnSpanFull()->columns(3)->schema([
                TextEntry::make('name')->label(__('Article')), TextEntry::make('quantity')->label(__('Qté')), TextEntry::make('total')->label(__('Total'))->formatStateUsing(fn ($state) => money($state)),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('created_at', 'desc')->columns([
            Tables\Columns\TextColumn::make('reference')->label(__('Réf.'))->searchable()->fontFamily('mono'),
            Tables\Columns\TextColumn::make('type')->label(__('Type'))->badge(),
            Tables\Columns\TextColumn::make('user.name')->label(__('Client'))->searchable(),
            Tables\Columns\TextColumn::make('place')->label(__('Lieu / événement'))->state(fn ($record) => $record->restaurant?->name ?? $record->event?->title)->limit(40),
            Tables\Columns\TextColumn::make('total')->label(__('Total'))->formatStateUsing(fn ($state) => money($state))->sortable(),
            Tables\Columns\TextColumn::make('status')->label(__('Statut'))->badge()->formatStateUsing(fn ($state) => __(Order::STATUSES[$state] ?? $state))
                ->color(fn ($state) => match ($state) { 'en_attente' => 'warning', 'annulee' => 'danger', default => 'success' }),
            Tables\Columns\TextColumn::make('created_at')->label(__('Date'))->dateTime('d/m/Y H:i')->sortable(),
        ])->filters([
            Tables\Filters\SelectFilter::make('type')->options(['repas' => __('Repas'), 'billets' => __('Billets')]),
            Tables\Filters\SelectFilter::make('status')->label(__('Statut'))->options(collect(Order::STATUSES)->map(fn ($l) => __($l))),
        ])->actions([
            Tables\Actions\ViewAction::make(),
            Tables\Actions\Action::make('annuler')->label(__('Annuler'))->icon('heroicon-o-x-mark')->color('danger')->requiresConfirmation()
                ->visible(fn ($record) => ! in_array($record->status, ['annulee', 'terminee', 'livree'], true))
                ->action(fn (Order $record) => $record->status === 'en_attente' ? app(OrderService::class)->releaseStock($record) : app(OrderService::class)->advance($record, 'annulee')),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageOrders::route('/')];
    }
}
