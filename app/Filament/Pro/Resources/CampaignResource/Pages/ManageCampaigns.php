<?php

namespace App\Filament\Pro\Resources\CampaignResource\Pages;

use App\Filament\Pro\Resources\CampaignResource;
use App\Models\AdSlot;
use App\Models\User;
use App\Notifications\Ads\CampaignSubmitted;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

class ManageCampaigns extends ManageRecords
{
    protected static string $resource = CampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label(__('Nouvelle campagne'))
            ->mutateFormDataUsing(function (array $data) {
                $days = Carbon::parse($data['starts_on'])->diffInDays(Carbon::parse($data['ends_on'])) + 1;

                return $data + ['user_id' => auth()->id(), 'status' => 'en_attente', 'amount' => (int) $days * AdSlot::find($data['ad_slot_id'])->price_per_day];
            })
            ->after(function ($record) {
                if (! auth()->user()->hasRole('annonceur')) {
                    auth()->user()->addRole('annonceur');
                }
                Notification::send(User::admins(), new CampaignSubmitted($record->load('slot')));
            })];
    }
}
