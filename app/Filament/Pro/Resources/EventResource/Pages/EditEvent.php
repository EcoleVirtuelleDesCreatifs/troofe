<?php

namespace App\Filament\Pro\Resources\EventResource\Pages;

use App\Filament\Pro\Resources\EventResource;
use App\Services\ModerationService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEvent extends EditRecord
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('soumettre')->label(__('Soumettre à nouveau'))->icon('heroicon-o-paper-airplane')
                ->visible(fn () => in_array($this->record->status, ['rejected', 'draft'], true))
                ->action(function () {
                    $this->record->update(['status' => 'pending', 'moderation_note' => null]);
                    app(ModerationService::class)->submitted($this->record);
                }),
            Actions\DeleteAction::make(),
        ];
    }
}
