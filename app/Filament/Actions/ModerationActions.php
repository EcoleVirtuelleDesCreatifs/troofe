<?php

namespace App\Filament\Actions;

use App\Services\ModerationService;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Model;

/** Publier / refuser / suspendre une fiche, avec notification du propriétaire */
class ModerationActions
{
    public static function make(): ActionGroup
    {
        $decide = fn (string $status, string $label, string $icon, string $color, bool $withNote) => Action::make($status)
            ->label(__($label))->icon($icon)->color($color)
            ->visible(fn (Model $record) => $record->status !== $status)
            ->requiresConfirmation()
            ->form($withNote ? [Textarea::make('note')->label(__('Motif communiqué au propriétaire'))->required()] : [])
            ->action(function (Model $record, array $data) use ($status) {
                app(ModerationService::class)->decide($record, $status, $data['note'] ?? null);
                Notification::make()->title(__('Statut mis à jour, propriétaire prévenu.'))->success()->send();
            });

        return ActionGroup::make([
            $decide('published', 'Valider et publier', 'heroicon-o-check-circle', 'success', false),
            $decide('rejected', 'Refuser', 'heroicon-o-x-circle', 'danger', true),
            $decide('suspended', 'Suspendre', 'heroicon-o-pause-circle', 'warning', true),
        ])->label(__('Modération'))->icon('heroicon-o-shield-check')->button()->size('sm');
    }
}
