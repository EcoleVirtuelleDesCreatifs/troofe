<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PlanResource\Pages;
use App\Models\Plan;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationGroup(): ?string { return __('Abonnements'); }

    public static function getModelLabel(): string { return __('formule'); }

    public static function getPluralModelLabel(): string { return __('Formules pro'); }

    public static function form(Form $form): Form
    {
        return $form->columns(4)->schema([
            TextInput::make('slug')->required()->unique(ignoreRecord: true)->alphaDash(),
            TextInput::make('price_monthly')->label(__('Prix mensuel (FCFA)'))->numeric()->required(),
            TextInput::make('price_yearly')->label(__('Prix annuel (FCFA)'))->numeric()->required(),
            TextInput::make('position')->label(__('Ordre'))->numeric()->default(0),
            TextInput::make('max_listings')->label(__('Fiches actives max.'))->numeric()->required(),
            TextInput::make('commission_rate')->label(__('Commission (%)'))->numeric()->helperText(__('Vide = taux global')),
            Toggle::make('can_feature')->label(__('Mise en avant incluse'))->inline(false),
            Toggle::make('has_stats')->label(__('Statistiques détaillées'))->inline(false),
            Toggle::make('priority_support')->label(__('Support prioritaire'))->inline(false),
            Toggle::make('is_active')->label(__('Proposée à la vente'))->default(true)->inline(false),
            Tabs::make('langues')->columnSpanFull()->tabs([
                Tabs\Tab::make('Français')->schema([TextInput::make('name')->label(__('Nom'))->required(), TextInput::make('tagline')->label(__('Accroche')), TagsInput::make('features')->label(__('Avantages'))]),
                Tabs\Tab::make('English')->schema([TextInput::make('name_en')->label(__('Nom')), TextInput::make('tagline_en')->label(__('Accroche')), TagsInput::make('features_en')->label(__('Avantages'))]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->reorderable('position')->defaultSort('position')->columns([
            Tables\Columns\TextColumn::make('name')->label(__('Formule'))->description(fn ($record) => $record->tagline),
            Tables\Columns\TextColumn::make('price_monthly')->label(__('Mensuel'))->formatStateUsing(fn ($state) => money($state)),
            Tables\Columns\TextColumn::make('price_yearly')->label(__('Annuel'))->formatStateUsing(fn ($state) => money($state)),
            Tables\Columns\TextColumn::make('max_listings')->label(__('Fiches')),
            Tables\Columns\TextColumn::make('active_count')->label(__('Abonnés actifs'))->state(fn ($record) => $record->subscriptions()->whereIn('status', ['active', 'past_due', 'cancelled'])->count()),
            Tables\Columns\ToggleColumn::make('is_active')->label(__('En vente')),
        ])->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManagePlans::route('/')];
    }
}
