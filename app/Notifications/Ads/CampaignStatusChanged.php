<?php

namespace App\Notifications\Ads;

use App\Models\AdCampaign;
use App\Notifications\TroofeNotification;

class CampaignStatusChanged extends TroofeNotification
{
    public function __construct(public AdCampaign $campaign) {}

    public function category(): string { return 'publicite'; }

    public function title(): string { return __('Campagne « :title » : :status', ['title' => $this->campaign->title, 'status' => mb_strtolower(__($this->campaign->statusLabel()))]); }

    public function message(): string
    {
        return match ($this->campaign->status) {
            'a_payer' => __('Votre campagne est validée. Réglez :amount pour la diffuser du :from au :to.', ['amount' => money($this->campaign->amount), 'from' => date_human($this->campaign->starts_on, 'D MMM'), 'to' => date_human($this->campaign->ends_on, 'D MMM YYYY')]),
            'active' => __('Votre campagne est en diffusion jusqu\'au :to.', ['to' => date_human($this->campaign->ends_on)]),
            'refusee' => __('Votre campagne n\'a pas été acceptée. :note', ['note' => $this->campaign->moderation_note]),
            'terminee' => __('Bilan : :imp affichages, :clicks clics (taux de clic :ctr %).', ['imp' => number_format($this->campaign->impressions, 0, ',', ' '), 'clicks' => $this->campaign->clicks, 'ctr' => $this->campaign->ctr()]),
            default => __('Le statut de votre campagne a changé.'),
        };
    }

    public function url(): string { return $this->campaign->status === 'a_payer' ? route('campaigns.pay', $this->campaign) : url('/pro/campaigns'); }

    public function icon(): string { return 'bi-megaphone'; }

    protected function actionLabel(): string { return $this->campaign->status === 'a_payer' ? __('Payer la campagne') : __('Voir la campagne'); }
}
