<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\SubscriptionResource\Pages;
use App\Models\Plan;
use App\Models\Subscription;
use App\Notifications\Billing\SubscriptionActivated;
use App\Services\ListingQuota;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    public static function getNavigationGroup(): ?string { return __('Abonnements'); }

    public static function getModelLabel(): string { return __('abonnement'); }

    public static function getPluralModelLabel(): string { return __('Abonnements pro'); }

    /** Création manuelle (offert, paiement hors ligne) */
    public static function form(Form $form): Form
    {
        return $form->columns(3)->schema([
            Select::make('user_id')->label(__('Professionnel'))->relationship('user', 'name')->searchable()->preload()->required(),
            Select::make('plan_id')->label(__('Formule'))->options(Plan::pluck('name', 'id'))->required(),
            Select::make('billing_cycle')->label(__('Cycle'))->options(['monthly' => __('Mensuel'), 'yearly' => __('Annuel')])->default('monthly')->required(),
            Select::make('status')->label(__('Statut'))->options(collect(Subscription::STATUSES)->map(fn ($l) => __($l)))->default('active')->required(),
            DateTimePicker::make('starts_at')->label(__('Début'))->default(now()),
            DateTimePicker::make('ends_at')->label(__('Échéance'))->default(now()->addMonth())->required(),
            Toggle::make('auto_renew')->label(__('Rappels de renouvellement'))->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('ends_at')->columns([
            Tables\Columns\TextColumn::make('user.name')->label(__('Professionnel'))->searchable()->description(fn ($record) => $record->user->email),
            Tables\Columns\TextColumn::make('plan.name')->label(__('Formule'))->badge(),
            Tables\Columns\TextColumn::make('billing_cycle')->label(__('Cycle'))->formatStateUsing(fn ($state) => $state === 'yearly' ? __('Annuel') : __('Mensuel')),
            Tables\Columns\TextColumn::make('ends_at')->label(__('Échéance'))->date('d/m/Y')->sortable()->description(fn ($record) => $record->ends_at && in_array($record->status, ['active', 'cancelled'], true) ? trans_choice(':count jour restant|:count jours restants', $record->daysLeft()) : null),
            Tables\Columns\TextColumn::make('status')->label(__('Statut'))->badge()->formatStateUsing(fn ($state) => __(Subscription::STATUSES[$state] ?? $state))
                ->color(fn ($state) => match ($state) { 'active' => 'success', 'pending' => 'gray', 'past_due' => 'warning', default => 'danger' }),
            Tables\Columns\IconColumn::make('auto_renew')->label(__('Reconduction'))->boolean(),
        ])->filters([
            Tables\Filters\SelectFilter::make('status')->label(__('Statut'))->options(collect(Subscription::STATUSES)->map(fn ($l) => __($l))),
            Tables\Filters\SelectFilter::make('plan_id')->label(__('Formule'))->relationship('plan', 'name'),
            Tables\Filters\Filter::make('bientot')->label(__('Échéance sous 15 jours'))->query(fn ($query) => $query->whereBetween('ends_at', [now(), now()->addDays(15)])),
        ])->actions([
            Tables\Actions\Action::make('prolonger')->label(__('Prolonger d\'une période'))->icon('heroicon-o-arrow-path')->requiresConfirmation()
                ->action(function (Subscription $record) {
                    $record->activateForPeriod();
                    app(ListingQuota::class)->restore($record->user);
                    $record->user->notify(new SubscriptionActivated($record->fresh('plan'), true));
                }),
            Tables\Actions\EditAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageSubscriptions::route('/')];
    }
}
