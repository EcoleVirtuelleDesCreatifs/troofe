<?php

namespace App\Filament\Pro\Resources;

use App\Filament\Pro\Resources\CampaignResource\Pages;
use App\Models\AdCampaign;
use App\Models\AdSlot;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class CampaignResource extends Resource
{
    protected static ?string $model = AdCampaign::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $slug = 'campaigns';

    public static function getNavigationGroup(): ?string { return __('Activité'); }

    public static function getModelLabel(): string { return __('campagne'); }

    public static function getPluralModelLabel(): string { return __('Mes campagnes'); }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function canEdit($record): bool
    {
        return $record->status === 'en_attente';
    }

    public static function form(Form $form): Form
    {
        $total = function (Get $get) {
            $slot = AdSlot::find($get('ad_slot_id'));
            if (! $slot || ! $get('starts_on') || ! $get('ends_on')) {
                return '—';
            }
            $days = Carbon::parse($get('starts_on'))->diffInDays(Carbon::parse($get('ends_on'))) + 1;

            return $days > 0 ? money($days * $slot->price_per_day).' · '.trans_choice(':count jour|:count jours', $days) : '—';
        };

        return $form->columns(3)->schema([
            Select::make('ad_slot_id')->label(__('Emplacement'))->options(AdSlot::where('is_active', true)->get()->mapWithKeys(fn ($state) => [$state->id => $state->name.' — '.money($state->price_per_day).' / '.__('jour')]))->required()->live()->columnSpan(2),
            TextInput::make('advertiser')->label(__('Annonceur'))->required()->default(fn () => auth()->user()->name),
            TextInput::make('title')->label(__('Titre'))->required()->maxLength(80),
            TextInput::make('cta')->label(__('Texte du bouton'))->required()->default(__('Découvrir'))->maxLength(30),
            TextInput::make('url')->label(__('Lien'))->url()->required(),
            Textarea::make('text')->label(__('Texte'))->maxLength(200)->columnSpanFull(),
            FileUpload::make('image')->label(__('Visuel'))->image()->imageEditor()->directory('campagnes')->maxSize(2048)->columnSpanFull(),
            DatePicker::make('starts_on')->label(__('Début'))->required()->minDate(today())->live(),
            DatePicker::make('ends_on')->label(__('Fin'))->required()->afterOrEqual('starts_on')->live(),
            Placeholder::make('total')->label(__('Montant'))->content($total),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('created_at', 'desc')->columns([
            Tables\Columns\ImageColumn::make('image')->label(''),
            Tables\Columns\TextColumn::make('title')->label(__('Campagne'))->description(fn ($record) => $record->slot->name),
            Tables\Columns\TextColumn::make('period')->label(__('Période'))->state(fn ($record) => $record->starts_on->format('d/m').' → '.$record->ends_on->format('d/m/Y')),
            Tables\Columns\TextColumn::make('amount')->label(__('Montant'))->formatStateUsing(fn ($state) => money($state)),
            Tables\Columns\TextColumn::make('perf')->label(__('Affichages / clics'))->state(fn ($record) => number_format($record->impressions, 0, ',', ' ').' / '.$record->clicks),
            Tables\Columns\TextColumn::make('status')->label(__('Statut'))->badge()->formatStateUsing(fn ($state) => __(AdCampaign::STATUSES[$state] ?? $state))
                ->color(fn ($state) => match ($state) { 'active' => 'success', 'en_attente', 'a_payer' => 'warning', 'terminee' => 'gray', default => 'danger' })
                ->description(fn ($record) => $record->status === 'refusee' ? $record->moderation_note : null),
        ])->actions([
            Tables\Actions\Action::make('payer')->label(__('Payer'))->icon('heroicon-o-credit-card')->button()->visible(fn ($record) => $record->status === 'a_payer')->url(fn ($record) => route('campaigns.pay', $record)),
            Tables\Actions\EditAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageCampaigns::route('/')];
    }
}
