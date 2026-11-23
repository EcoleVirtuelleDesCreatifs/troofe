<?php

namespace App\Filament\Pro\Resources\RestaurantResource\Pages;

use App\Filament\Pro\Resources\RestaurantResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRestaurants extends ListRecords
{
    protected static string $resource = RestaurantResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
