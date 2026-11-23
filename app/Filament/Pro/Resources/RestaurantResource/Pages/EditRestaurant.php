<?php

namespace App\Filament\Pro\Resources\RestaurantResource\Pages;

use App\Filament\Pro\Resources\RestaurantResource;
use App\Services\ModerationService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRestaurant extends EditRecord
{
    protected static string $resource = RestaurantResource::class;

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
