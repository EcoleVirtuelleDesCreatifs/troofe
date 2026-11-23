<?php

namespace App\Filament\Admin\Resources\NewsletterSubscriberResource\Pages;

use App\Filament\Admin\Resources\NewsletterSubscriberResource;
use Filament\Resources\Pages\ManageRecords;

class ManageNewsletterSubscribers extends ManageRecords
{
    protected static string $resource = NewsletterSubscriberResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
