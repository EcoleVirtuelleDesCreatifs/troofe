<?php

namespace App\Filament\Pro\Resources\TalentResource\Pages;

use App\Filament\Pro\Resources\TalentResource;
use App\Services\ModerationService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTalent extends EditRecord
{
    protected static string $resource = TalentResource::class;

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
