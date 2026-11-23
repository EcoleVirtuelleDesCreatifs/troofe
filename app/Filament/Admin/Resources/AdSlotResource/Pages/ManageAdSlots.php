<?php

namespace App\Filament\Admin\Resources\AdSlotResource\Pages;

use App\Filament\Admin\Resources\AdSlotResource;
use Filament\Resources\Pages\ManageRecords;

class ManageAdSlots extends ManageRecords
{
    protected static string $resource = AdSlotResource::class;

    protected function getHeaderActions(): array
    {
        return [\Filament\Actions\CreateAction::make()];
    }
}
