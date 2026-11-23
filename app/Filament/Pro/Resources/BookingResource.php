<?php

namespace App\Filament\Pro\Resources;

use App\Filament\Pro\Resources\BookingResource\Pages;
use App\Models\Booking;
use App\Services\BookingService;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** Demandes reçues : devis, confirmation, refus, prestation réalisée */
class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $slug = 'bookings';

    public static function getNavigationGroup(): ?string { return __('Activité'); }

    public static function getModelLabel(): string { return __('demande'); }

    public static function getPluralModelLabel(): string { return __('Réservations reçues'); }

    public static function canCreate(): bool { return false; }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('artiste', 'admin') ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereHas('talent', fn ($query) => $query->where('owner_id', auth()->id()));
    }

    public static function getNavigationBadge(): ?string
    {
        $n = static::getEloquentQuery()->where('status', 'nouvelle')->count();

        return $n ? (string) $n : null;
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->columns(3)->schema([
            TextEntry::make('reference')->label(__('Référence')),
            TextEntry::make('kind')->label(__('Nature'))->formatStateUsing(fn ($state) => __(Booking::KINDS[$state] ?? $state)),
            TextEntry::make('status')->label(__('Statut'))->formatStateUsing(fn ($state) => __(Booking::STATUSES[$state] ?? $state)),
            TextEntry::make('user.name')->label(__('Client')),
            TextEntry::make('contact_phone')->label(__('Téléphone'))->copyable(),
            TextEntry::make('user.email')->label('E-mail')->copyable(),
            TextEntry::make('offer.name')->label(__('Formule / création'))->placeholder('—'),
            TextEntry::make('event_date')->label(__('Date'))->formatStateUsing(fn ($state) => date_human($state))->placeholder('—'),
            TextEntry::make('occasion')->label(__('Occasion'))->placeholder('—'),
            TextEntry::make('venue')->label(__('Lieu'))->placeholder('—'),
            TextEntry::make('city')->label(__('Ville'))->placeholder('—'),
            TextEntry::make('guests')->label(__('Invités / personnes'))->placeholder('—'),
            TextEntry::make('quantity')->label(__('Quantité'))->placeholder('—'),
            TextEntry::make('amount')->label(__('Montant'))->formatStateUsing(fn ($state) => $state ? money($state) : __('Devis à établir')),
            TextEntry::make('message')->label(__('Message'))->placeholder('—')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        $go = fn (string $status, string $label, string $color, string $icon) => Tables\Actions\Action::make($status)->label(__($label))->color($color)->icon($icon)->requiresConfirmation()
            ->action(function (Booking $record) use ($status) {
                app(BookingService::class)->transition($record, $status, auth()->user());
                Notification::make()->title(__('Le client a été prévenu par e-mail.'))->success()->send();
            });

        return $table->defaultSort('created_at', 'desc')->columns([
            Tables\Columns\TextColumn::make('reference')->label(__('Réf.'))->fontFamily('mono'),
            Tables\Columns\TextColumn::make('talent.name')->label(__('Fiche'))->description(fn ($record) => __(Booking::KINDS[$record->kind])),
            Tables\Columns\TextColumn::make('user.name')->label(__('Client'))->description(fn ($record) => $record->contact_phone),
            Tables\Columns\TextColumn::make('event_date')->label(__('Date'))->date('d/m/Y')->sortable()->placeholder('—'),
            Tables\Columns\TextColumn::make('offer.name')->label(__('Formule'))->limit(28)->placeholder('—'),
            Tables\Columns\TextColumn::make('amount')->label(__('Montant'))->formatStateUsing(fn ($state) => $state ? money($state) : '—'),
            Tables\Columns\TextColumn::make('status')->label(__('Statut'))->badge()->formatStateUsing(fn ($state) => __(Booking::STATUSES[$state] ?? $state))
                ->color(fn ($state) => match ($state) { 'nouvelle' => 'warning', 'devis_envoye' => 'info', 'confirmee', 'realisee' => 'success', default => 'danger' }),
        ])->filters([
            Tables\Filters\SelectFilter::make('status')->label(__('Statut'))->options(collect(Booking::STATUSES)->map(fn ($l) => __($l))),
        ])->actions([
            Tables\Actions\Action::make('devis')->label(__('Envoyer un devis'))->icon('heroicon-o-document-text')->color('primary')->button()
                ->visible(fn (Booking $record) => $record->kind === 'prestation' && in_array($record->status, ['nouvelle', 'devis_envoye'], true))
                ->fillForm(fn (Booking $record) => ['amount' => $record->amount ?: $record->offer?->price, 'quote_note' => $record->quote_note ?? __('Acompte de 30 % à la confirmation, solde le jour de la prestation.')])
                ->form([TextInput::make('amount')->label(__('Montant (FCFA)'))->numeric()->required()->minValue(0), Textarea::make('quote_note')->label(__('Conditions et précisions'))->rows(3)])
                ->action(function (Booking $record, array $data) {
                    app(BookingService::class)->transition($record, 'devis_envoye', auth()->user(), $data);
                    Notification::make()->title(__('Devis envoyé au client.'))->success()->send();
                }),
            $go('confirmee', 'Confirmer', 'success', 'heroicon-o-check')->visible(fn (Booking $record) => $record->kind !== 'prestation' && $record->status === 'nouvelle'),
            $go('refusee', 'Refuser', 'danger', 'heroicon-o-x-mark')->visible(fn (Booking $record) => in_array($record->status, ['nouvelle', 'devis_envoye'], true)),
            $go('realisee', 'Marquer réalisée', 'gray', 'heroicon-o-flag')->visible(fn (Booking $record) => $record->status === 'confirmee'),
            Tables\Actions\ViewAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageBookings::route('/')];
    }
}
