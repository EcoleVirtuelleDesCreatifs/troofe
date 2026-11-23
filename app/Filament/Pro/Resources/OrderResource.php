<?php

namespace App\Filament\Pro\Resources;

use App\Filament\Pro\Resources\OrderResource\Pages;
use App\Models\Order;
use App\Services\OrderService;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** Commandes reçues (restaurants) et ventes de billets (événements) */
class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $slug = 'orders';

    public static function getNavigationGroup(): ?string { return __('Activité'); }

    public static function getModelLabel(): string { return __('commande'); }

    public static function getPluralModelLabel(): string { return __('Commandes reçues'); }

    public static function canCreate(): bool { return false; }

    public static function getEloquentQuery(): Builder
    {
        $uid = auth()->id();

        return parent::getEloquentQuery()->where('status', '!=', 'en_attente')
            ->where(fn ($query) => $query->whereHas('restaurant', fn ($record) => $record->where('owner_id', $uid))->orWhereHas('event', fn ($e) => $e->where('organizer_id', $uid)));
    }

    public static function getNavigationBadge(): ?string
    {
        $n = static::getEloquentQuery()->whereIn('status', ['payee', 'confirmee', 'en_preparation'])->where('type', 'repas')->count();

        return $n ? (string) $n : null;
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->columns(3)->schema([
            TextEntry::make('reference')->label(__('Référence')),
            TextEntry::make('user.name')->label(__('Client')),
            TextEntry::make('contact_phone')->label(__('Téléphone'))->copyable(),
            TextEntry::make('fulfilment')->label(__('Remise'))->placeholder('—'),
            TextEntry::make('delivery_address')->label(__('Adresse'))->placeholder('—')->columnSpan(2),
            TextEntry::make('note')->label(__('Note du client'))->placeholder('—')->columnSpanFull(),
            RepeatableEntry::make('items')->label(__('Articles'))->columnSpanFull()->columns(3)->schema([
                TextEntry::make('name')->label(__('Article')), TextEntry::make('quantity')->label(__('Qté')), TextEntry::make('total')->label(__('Total'))->formatStateUsing(fn ($state) => money($state)),
            ]),
            TextEntry::make('total')->label(__('Total payé'))->formatStateUsing(fn ($state) => money($state)),
            TextEntry::make('commission')->label(__('Commission Trôofê'))->formatStateUsing(fn ($state) => money($state)),
            TextEntry::make('net')->label(__('Net à percevoir'))->state(fn ($record) => money($record->total - $record->commission)),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('created_at', 'desc')->poll('30s')->columns([
            Tables\Columns\TextColumn::make('reference')->label(__('Réf.'))->fontFamily('mono')->searchable(),
            Tables\Columns\TextColumn::make('created_at')->label(__('Reçue'))->since()->sortable(),
            Tables\Columns\TextColumn::make('user.name')->label(__('Client')),
            Tables\Columns\TextColumn::make('place')->label(__('Pour'))->state(fn ($record) => $record->restaurant?->name ?? $record->event?->title)->limit(30),
            Tables\Columns\TextColumn::make('fulfilment')->label(__('Remise'))->badge()->placeholder(__('Billets')),
            Tables\Columns\TextColumn::make('total')->label(__('Total'))->formatStateUsing(fn ($state) => money($state)),
            Tables\Columns\TextColumn::make('status')->label(__('Statut'))->badge()->formatStateUsing(fn ($state) => __(Order::STATUSES[$state] ?? $state))
                ->color(fn ($state) => match ($state) { 'payee' => 'warning', 'annulee' => 'danger', 'terminee', 'livree' => 'gray', default => 'success' }),
        ])->filters([
            Tables\Filters\SelectFilter::make('status')->label(__('Statut'))->options(collect(Order::STATUSES)->except('en_attente')->map(fn ($l) => __($l))),
        ])->actions([
            Tables\Actions\Action::make('suivant')
                ->label(fn (Order $record) => __(Order::STATUSES[Order::KITCHEN_FLOW[$record->status] ?? ''] ?? ''))
                ->icon('heroicon-o-arrow-right-circle')->color('primary')->button()
                ->visible(fn (Order $record) => $record->type === 'repas' && isset(Order::KITCHEN_FLOW[$record->status]))
                ->action(fn (Order $record) => app(OrderService::class)->advance($record, Order::KITCHEN_FLOW[$record->status])),
            Tables\Actions\Action::make('refuser')->label(__('Refuser'))->color('danger')->icon('heroicon-o-x-mark')->requiresConfirmation()
                ->modalDescription(__('Le client est prévenu et remboursé par l\'équipe Trôofê.'))
                ->visible(fn (Order $record) => $record->type === 'repas' && $record->status === 'payee')
                ->action(fn (Order $record) => app(OrderService::class)->advance($record, 'annulee')),
            Tables\Actions\ViewAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageOrders::route('/')];
    }
}
