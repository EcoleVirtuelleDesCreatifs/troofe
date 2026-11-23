<?php

namespace App\Filament\Pro\Resources\OrderResource\Pages;

use App\Filament\Pro\Resources\OrderResource;
use Filament\Resources\Pages\ManageRecords;

class ManageOrders extends ManageRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
