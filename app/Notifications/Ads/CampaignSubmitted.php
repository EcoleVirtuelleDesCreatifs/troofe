<?php

namespace App\Notifications\Ads;

use App\Models\AdCampaign;
use App\Notifications\TroofeNotification;

class CampaignSubmitted extends TroofeNotification
{
    public function __construct(public AdCampaign $campaign) {}

    public function category(): string { return 'moderation'; }

    public function title(): string { return __('Campagne à valider : :title', ['title' => $this->campaign->title]); }

    public function message(): string { return __(':advertiser — :slot, du :from au :to (:amount).', ['advertiser' => $this->campaign->advertiser, 'slot' => $this->campaign->slot->name, 'from' => date_human($this->campaign->starts_on, 'D MMM'), 'to' => date_human($this->campaign->ends_on, 'D MMM'), 'amount' => money($this->campaign->amount)]); }

    public function url(): string { return url('/admin/ad-campaigns'); }

    public function icon(): string { return 'bi-megaphone'; }
}
