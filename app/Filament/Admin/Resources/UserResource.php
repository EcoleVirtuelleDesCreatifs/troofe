<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\UserResource\Pages;
use App\Models\User;
use App\Notifications\Account\RoleGrantedNotification;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function getNavigationGroup(): ?string { return __('Communauté'); }

    public static function getModelLabel(): string { return __('utilisateur'); }

    public static function getPluralModelLabel(): string { return __('Utilisateurs'); }

    public static function form(Form $form): Form
    {
        return $form->columns(3)->schema([
            TextInput::make('name')->label(__('Nom'))->required(),
            TextInput::make('email')->label('E-mail')->email()->required()->unique(ignoreRecord: true),
            TextInput::make('phone')->label(__('Téléphone')),
            TextInput::make('city')->label(__('Ville')),
            Select::make('locale')->label(__('Langue'))->options(['fr' => 'Français', 'en' => 'English'])->default('fr'),
            Toggle::make('is_active')->label(__('Compte actif'))->default(true)->inline(false),
            CheckboxList::make('roles')->label(__('Rôles'))->options(collect(User::ROLES)->map(fn ($l) => __($l)))->columns(3)->columnSpanFull(),
            TextInput::make('password')->label(__('Mot de passe'))->password()->revealable()->dehydrated(fn ($state) => filled($state))->required(fn (string $operation) => $operation === 'create')->minLength(8),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('created_at', 'desc')->columns([
            Tables\Columns\TextColumn::make('name')->label(__('Nom'))->searchable()->description(fn ($record) => $record->email),
            Tables\Columns\TextColumn::make('roles')->label(__('Rôles'))->badge()->formatStateUsing(fn ($state) => __(User::ROLES[$state] ?? $state)),
            Tables\Columns\TextColumn::make('activeSubscription.plan.name')->label(__('Formule'))->placeholder(__('Découverte')),
            Tables\Columns\IconColumn::make('email_verified_at')->label(__('Vérifié'))->boolean()->state(fn ($record) => (bool) $record->email_verified_at),
            Tables\Columns\TextColumn::make('locale')->label(__('Langue'))->badge(),
            Tables\Columns\ToggleColumn::make('is_active')->label(__('Actif')),
            Tables\Columns\TextColumn::make('last_login_at')->label(__('Dernière connexion'))->since()->sortable(),
        ])->filters([
            Tables\Filters\SelectFilter::make('role')->label(__('Rôle'))->options(collect(User::ROLES)->map(fn ($l) => __($l)))->query(fn ($query, $data) => $data['value'] ? $query->whereJsonContains('roles', $data['value']) : $query),
            Tables\Filters\TernaryFilter::make('email_verified_at')->label(__('E-mail vérifié'))->nullable(),
        ])->actions([
            Tables\Actions\Action::make('verifier')->label(__('Valider l\'e-mail'))->icon('heroicon-o-envelope-open')->visible(fn ($record) => ! $record->email_verified_at)->requiresConfirmation()->action(fn ($record) => $record->markEmailAsVerified()),
            Tables\Actions\EditAction::make()->using(function (User $record, array $data) {
                $old = $record->roles ?? [];
                $record->update($data);
                foreach (array_diff($record->roles ?? [], $old) as $role) {
                    if (in_array($role, User::PRO_ROLES, true)) {
                        $record->notify(new RoleGrantedNotification($role));
                    }
                }

                return $record;
            }),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageUsers::route('/')];
    }
}
