<?php

namespace App\Filament\RelationManagers;

use App\Notifications\Engagement\FollowedUpdate;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification;

/** Formules (troupes, artistes) ou catalogue (artisans) */
class OffersRelationManager extends RelationManager
{
    protected static string $relationship = 'offers';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return $ownerRecord->type === 'artisan' ? __('Catalogue') : __('Formules');
    }

    public function form(Form $form): Form
    {
        $craft = $this->getOwnerRecord()->type === 'artisan';

        return $form->columns(3)->schema([
            TextInput::make('price')->label(__('Prix (FCFA)'))->numeric()->required()->minValue(0),
            TextInput::make($craft ? 'lead_time' : 'duration')->label($craft ? __('Délai de fabrication') : __('Durée')),
            Toggle::make('is_custom')->label(__('Sur mesure'))->visible($craft)->inline(false),
            Tabs::make('langues')->columnSpanFull()->tabs([
                Tabs\Tab::make('Français')->schema([TextInput::make('name')->label(__('Nom'))->required(), Textarea::make('details')->label(__('Détails'))->rows(2)]),
                Tabs\Tab::make('English')->schema([TextInput::make('name_en')->label(__('Nom')), Textarea::make('details_en')->label(__('Détails'))->rows(2)]),
            ]),
            FileUpload::make('image')->label(__('Photo'))->image()->directory('culture')->maxSize(2048)->visible($craft)->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->reorderable('position')
            ->columns([
                Tables\Columns\TextColumn::make('name')->label(__('Nom'))->description(fn ($record) => $record->details),
                Tables\Columns\TextColumn::make('duration')->label(__('Durée / délai'))->formatStateUsing(fn ($state, $record) => $record->duration ?: $record->lead_time),
                Tables\Columns\TextColumn::make('price')->label(__('Prix'))->formatStateUsing(fn ($state) => money($state))->sortable(),
                Tables\Columns\IconColumn::make('is_custom')->label(__('Sur mesure'))->boolean(),
            ])
            ->headerActions([Tables\Actions\CreateAction::make()->after(function (Model $record) {
                // Prévenir les abonnés de la fiche d'une nouveauté
                $talent = $this->getOwnerRecord();
                if ($talent->isPublished()) {
                    Notification::send($talent->followers()->get(), new FollowedUpdate($talent->name, __('nouvelle offre'), $record->name.' — '.money($record->price), $talent->url()));
                }
            })])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }
}
