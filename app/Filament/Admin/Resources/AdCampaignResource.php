<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\AdCampaignResource\Pages;
use App\Models\AdCampaign;
use App\Notifications\Ads\CampaignStatusChanged;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AdCampaignResource extends Resource
{
    protected static ?string $model = AdCampaign::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    public static function getNavigationGroup(): ?string { return __('Régie publicitaire'); }

    public static function getModelLabel(): string { return __('campagne'); }

    public static function getPluralModelLabel(): string { return __('Campagnes'); }

    public static function getNavigationBadge(): ?string
    {
        $n = AdCampaign::where('status', 'en_attente')->count();

        return $n ? (string) $n : null;
    }

    public static function form(Form $form): Form
    {
        return $form->columns(3)->schema([
            Select::make('ad_slot_id')->label(__('Emplacement'))->relationship('slot', 'name')->required(),
            TextInput::make('advertiser')->label(__('Annonceur'))->required(),
            Select::make('status')->label(__('Statut'))->options(collect(AdCampaign::STATUSES)->map(fn ($l) => __($l)))->default('active')->required(),
            TextInput::make('title')->label(__('Titre'))->required()->maxLength(80),
            TextInput::make('cta')->label(__('Bouton'))->default('Découvrir')->required(),
            TextInput::make('url')->label(__('Lien'))->url()->required(),
            Textarea::make('text')->label(__('Texte'))->maxLength(200)->columnSpanFull(),
            FileUpload::make('image')->label(__('Visuel'))->image()->directory('campagnes')->columnSpanFull(),
            DatePicker::make('starts_on')->label(__('Début'))->required(),
            DatePicker::make('ends_on')->label(__('Fin'))->required()->afterOrEqual('starts_on'),
            TextInput::make('amount')->label(__('Montant (FCFA)'))->numeric()->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        $decide = fn (string $status, string $label, string $color, bool $note) => Tables\Actions\Action::make($status)->label(__($label))->color($color)->requiresConfirmation()
            ->visible(fn ($record) => $record->status === 'en_attente')
            ->form($note ? [Textarea::make('note')->label(__('Motif'))->required()] : [])
            ->action(function (AdCampaign $record, array $data) use ($status) {
                $record->update(['status' => $status, 'moderation_note' => $data['note'] ?? null]);
                $record->user?->notify(new CampaignStatusChanged($record));
            });

        return $table->defaultSort('created_at', 'desc')->columns([
            Tables\Columns\ImageColumn::make('image')->label(''),
            Tables\Columns\TextColumn::make('title')->label(__('Campagne'))->description(fn ($record) => $record->advertiser)->searchable(),
            Tables\Columns\TextColumn::make('slot.name')->label(__('Emplacement')),
            Tables\Columns\TextColumn::make('period')->label(__('Période'))->state(fn ($record) => $record->starts_on->format('d/m').' → '.$record->ends_on->format('d/m/Y')),
            Tables\Columns\TextColumn::make('amount')->label(__('Montant'))->formatStateUsing(fn ($state) => money($state)),
            Tables\Columns\TextColumn::make('perf')->label(__('Affichages / clics'))->state(fn ($record) => number_format($record->impressions, 0, ',', ' ').' / '.$record->clicks.' ('.$record->ctr().' %)'),
            Tables\Columns\TextColumn::make('status')->label(__('Statut'))->badge()->formatStateUsing(fn ($state) => __(AdCampaign::STATUSES[$state] ?? $state))
                ->color(fn ($state) => match ($state) { 'active' => 'success', 'en_attente', 'a_payer' => 'warning', 'terminee' => 'gray', default => 'danger' }),
        ])->filters([Tables\Filters\SelectFilter::make('status')->label(__('Statut'))->options(collect(AdCampaign::STATUSES)->map(fn ($l) => __($l)))])
            ->actions([$decide('a_payer', 'Valider', 'success', false), $decide('refusee', 'Refuser', 'danger', true), Tables\Actions\EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageAdCampaigns::route('/')];
    }
}
