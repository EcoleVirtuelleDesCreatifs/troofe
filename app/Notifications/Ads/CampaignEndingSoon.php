<?php

namespace App\Notifications\Ads;

use App\Models\AdCampaign;
use App\Notifications\TroofeNotification;

class CampaignEndingSoon extends TroofeNotification
{
    public function __construct(public AdCampaign $campaign) {}

    public function category(): string { return 'publicite'; }

    public function title(): string { return __('Votre campagne « :title » se termine bientôt', ['title' => $this->campaign->title]); }

    public function message(): string { return __('Fin de diffusion le :date. :imp affichages et :clicks clics à ce jour. Prolongez-la depuis votre espace.', ['date' => date_human($this->campaign->ends_on), 'imp' => number_format($this->campaign->impressions, 0, ',', ' '), 'clicks' => $this->campaign->clicks]); }

    public function url(): string { return url('/pro/campaigns'); }

    public function icon(): string { return 'bi-hourglass-split'; }
}
