<?php

namespace App\Filament\Pro\Resources\BookingResource\Pages;

use App\Filament\Pro\Resources\BookingResource;
use Filament\Resources\Pages\ManageRecords;

class ManageBookings extends ManageRecords
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
