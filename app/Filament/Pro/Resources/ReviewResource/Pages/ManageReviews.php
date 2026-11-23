<?php

namespace App\Filament\Pro\Resources\ReviewResource\Pages;

use App\Filament\Pro\Resources\ReviewResource;
use Filament\Resources\Pages\ManageRecords;

class ManageReviews extends ManageRecords
{
    protected static string $resource = ReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
