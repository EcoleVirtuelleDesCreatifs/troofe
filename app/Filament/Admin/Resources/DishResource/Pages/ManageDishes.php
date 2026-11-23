<?php

namespace App\Filament\Admin\Resources\DishResource\Pages;

use App\Filament\Admin\Resources\DishResource;
use Filament\Resources\Pages\ManageRecords;

class ManageDishes extends ManageRecords
{
    protected static string $resource = DishResource::class;

    protected function getHeaderActions(): array
    {
        return [\Filament\Actions\CreateAction::make()];
    }
}
