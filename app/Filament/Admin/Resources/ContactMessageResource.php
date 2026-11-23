<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ContactMessageResource\Pages;
use App\Models\ContactMessage;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ContactMessageResource extends Resource
{
    protected static ?string $model = ContactMessage::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox';

    public static function getNavigationGroup(): ?string { return __('Communauté'); }

    public static function getModelLabel(): string { return __('message'); }

    public static function getPluralModelLabel(): string { return __('Messages de contact'); }

    public static function canCreate(): bool { return false; }

    public static function getNavigationBadge(): ?string
    {
        $n = ContactMessage::where('status', 'nouveau')->count();

        return $n ? (string) $n : null;
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->columns(3)->schema([
            TextEntry::make('name')->label(__('Nom')), TextEntry::make('email')->copyable(), TextEntry::make('created_at')->label(__('Reçu le'))->dateTime('d/m/Y H:i'),
            TextEntry::make('subject')->label(__('Objet'))->columnSpanFull(), TextEntry::make('message')->label(__('Message'))->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('created_at', 'desc')->columns([
            Tables\Columns\TextColumn::make('name')->label(__('Nom'))->description(fn ($record) => $record->email)->searchable(),
            Tables\Columns\TextColumn::make('subject')->label(__('Objet'))->limit(60),
            Tables\Columns\TextColumn::make('status')->label(__('Statut'))->badge()->color(fn ($state) => $state === 'nouveau' ? 'warning' : 'success'),
            Tables\Columns\TextColumn::make('created_at')->label(__('Reçu'))->since(),
        ])->actions([
            Tables\Actions\ViewAction::make()->after(fn ($record) => $record->update(['status' => 'lu'])),
            Tables\Actions\Action::make('repondre')->label(__('Répondre'))->icon('heroicon-o-envelope')->url(fn ($record) => 'mailto:'.$record->email.'?subject='.rawurlencode('Re: '.$record->subject)),
            Tables\Actions\DeleteAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageContactMessages::route('/')];
    }
}
