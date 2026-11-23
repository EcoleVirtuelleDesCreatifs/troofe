<?php

namespace App\Filament\Admin\Resources\AdCampaignResource\Pages;

use App\Filament\Admin\Resources\AdCampaignResource;
use Filament\Resources\Pages\ManageRecords;

class ManageAdCampaigns extends ManageRecords
{
    protected static string $resource = AdCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [\Filament\Actions\CreateAction::make()];
    }
}
