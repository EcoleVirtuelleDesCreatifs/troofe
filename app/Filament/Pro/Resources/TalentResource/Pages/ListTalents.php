<?php

namespace App\Filament\Pro\Resources\TalentResource\Pages;

use App\Filament\Pro\Resources\TalentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTalents extends ListRecords
{
    protected static string $resource = TalentResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
