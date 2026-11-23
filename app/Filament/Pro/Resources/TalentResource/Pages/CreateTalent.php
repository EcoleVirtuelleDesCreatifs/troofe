<?php

namespace App\Filament\Pro\Resources\TalentResource\Pages;

use App\Filament\Pro\Concerns\CreatesListing;
use App\Filament\Pro\Resources\TalentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTalent extends CreateRecord
{
    use CreatesListing;

    protected static string $resource = TalentResource::class;

    protected static ?string $ownerColumn = 'owner_id';
}
