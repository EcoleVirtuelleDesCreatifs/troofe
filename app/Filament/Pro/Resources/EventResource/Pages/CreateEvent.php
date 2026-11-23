<?php

namespace App\Filament\Pro\Resources\EventResource\Pages;

use App\Filament\Pro\Concerns\CreatesListing;
use App\Filament\Pro\Resources\EventResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEvent extends CreateRecord
{
    use CreatesListing;

    protected static string $resource = EventResource::class;

    protected static ?string $ownerColumn = 'organizer_id';
}
