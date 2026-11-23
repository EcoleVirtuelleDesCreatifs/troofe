<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\BookingResource\Pages;
use App\Models\Booking;
use App\Services\BookingService;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    public static function getNavigationGroup(): ?string { return __('Commerce'); }

    public static function getModelLabel(): string { return __('réservation'); }

    public static function getPluralModelLabel(): string { return __('Réservations culture'); }

    public static function canCreate(): bool { return false; }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->columns(3)->schema([
            TextEntry::make('reference')->label(__('Référence')),
            TextEntry::make('talent.name')->label(__('Fiche')),
            TextEntry::make('kind')->label(__('Nature'))->formatStateUsing(fn ($state) => __(Booking::KINDS[$state] ?? $state)),
            TextEntry::make('user.name')->label(__('Client')),
            TextEntry::make('contact_phone')->label(__('Téléphone')),
            TextEntry::make('event_date')->label(__('Date'))->date('d/m/Y')->placeholder('—'),
            TextEntry::make('offer.name')->label(__('Formule / création'))->placeholder('—'),
            TextEntry::make('amount')->label(__('Montant'))->formatStateUsing(fn ($state) => $state ? money($state) : __('Devis à établir')),
            TextEntry::make('status')->label(__('Statut'))->formatStateUsing(fn ($state) => __(Booking::STATUSES[$state] ?? $state)),
            TextEntry::make('venue')->label(__('Lieu'))->placeholder('—'),
            TextEntry::make('message')->label(__('Message'))->placeholder('—')->columnSpan(2),
            TextEntry::make('history')->label(__('Historique'))->columnSpanFull()->listWithLineBreaks()
                ->state(fn ($record) => collect($record->history)->map(fn ($h) => date_human($h['at'], 'D MMM YYYY HH:mm').' — '.__(Booking::STATUSES[$h['status']] ?? $h['status']).' — '.($h['by'] ?? ''))->all()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('created_at', 'desc')->columns([
            Tables\Columns\TextColumn::make('reference')->label(__('Réf.'))->searchable()->fontFamily('mono'),
            Tables\Columns\TextColumn::make('talent.name')->label(__('Fiche'))->searchable()->description(fn ($record) => __(Booking::KINDS[$record->kind])),
            Tables\Columns\TextColumn::make('user.name')->label(__('Client')),
            Tables\Columns\TextColumn::make('event_date')->label(__('Date'))->date('d/m/Y')->sortable()->placeholder('—'),
            Tables\Columns\TextColumn::make('amount')->label(__('Montant'))->formatStateUsing(fn ($state) => $state ? money($state) : '—'),
            Tables\Columns\TextColumn::make('status')->label(__('Statut'))->badge()->formatStateUsing(fn ($state) => __(Booking::STATUSES[$state] ?? $state))
                ->color(fn ($state) => match ($state) { 'nouvelle' => 'warning', 'devis_envoye' => 'info', 'confirmee', 'realisee' => 'success', default => 'danger' }),
        ])->filters([
            Tables\Filters\SelectFilter::make('status')->label(__('Statut'))->options(collect(Booking::STATUSES)->map(fn ($l) => __($l))),
            Tables\Filters\SelectFilter::make('kind')->label(__('Nature'))->options(collect(Booking::KINDS)->map(fn ($l) => __($l))),
        ])->actions([
            Tables\Actions\ViewAction::make(),
            Tables\Actions\Action::make('annuler')->label(__('Annuler'))->icon('heroicon-o-x-mark')->color('danger')->requiresConfirmation()
                ->visible(fn ($record) => $record->status === 'confirmee')
                ->action(fn (Booking $record) => app(BookingService::class)->transition($record, 'annulee', auth()->user())),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageBookings::route('/')];
    }
}
