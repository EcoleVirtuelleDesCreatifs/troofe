<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PaymentResource\Pages;
use App\Models\Payment;
use App\Services\PaymentService;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    public static function getNavigationGroup(): ?string { return __('Commerce'); }

    public static function getModelLabel(): string { return __('paiement'); }

    public static function getPluralModelLabel(): string { return __('Paiements et factures'); }

    public static function canCreate(): bool { return false; }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('created_at', 'desc')->columns([
            Tables\Columns\TextColumn::make('reference')->label(__('Transaction'))->searchable()->fontFamily('mono')->copyable(),
            Tables\Columns\TextColumn::make('invoice_number')->label(__('Facture'))->placeholder('—')->searchable(),
            Tables\Columns\TextColumn::make('user.name')->label(__('Client'))->searchable(),
            Tables\Columns\TextColumn::make('description')->label(__('Objet'))->limit(45),
            Tables\Columns\TextColumn::make('amount')->label(__('Montant'))->formatStateUsing(fn ($state) => money($state))->sortable()->summarize(Tables\Columns\Summarizers\Sum::make()->formatStateUsing(fn ($state) => money($state))),
            Tables\Columns\TextColumn::make('method')->label(__('Moyen'))->placeholder('—'),
            Tables\Columns\TextColumn::make('status')->label(__('Statut'))->badge()->formatStateUsing(fn ($state) => __(Payment::STATUSES[$state] ?? $state))
                ->color(fn ($state) => match ($state) { 'paid' => 'success', 'pending' => 'warning', default => 'danger' }),
            Tables\Columns\TextColumn::make('paid_at')->label(__('Payé le'))->dateTime('d/m/Y H:i')->sortable(),
        ])->filters([
            Tables\Filters\SelectFilter::make('status')->label(__('Statut'))->options(collect(Payment::STATUSES)->map(fn ($l) => __($l))),
            Tables\Filters\SelectFilter::make('payable_type')->label(__('Objet'))->options(['subscription' => __('Abonnements'), 'order' => __('Commandes et billets'), 'ad_campaign' => __('Publicité')]),
        ])->actions([
            Tables\Actions\Action::make('verifier')->label(__('Vérifier auprès du prestataire'))->icon('heroicon-o-arrow-path')->visible(fn ($record) => $record->status === 'pending')
                ->action(fn (Payment $record) => app(PaymentService::class)->sync($record)),
            Tables\Actions\Action::make('facture')->label(__('Facture'))->icon('heroicon-o-document-arrow-down')->visible(fn ($record) => $record->isPaid())->url(fn ($record) => route('account.invoice', $record)),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManagePayments::route('/')];
    }
}
