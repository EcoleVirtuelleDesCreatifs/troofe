<?php

namespace App\Filament\Pro\Resources\RestaurantResource\Pages;

use App\Filament\Pro\Concerns\CreatesListing;
use App\Filament\Pro\Resources\RestaurantResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRestaurant extends CreateRecord
{
    use CreatesListing;

    protected static string $resource = RestaurantResource::class;

    protected static ?string $ownerColumn = 'owner_id';
}
