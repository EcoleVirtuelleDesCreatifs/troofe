<?php

namespace App\Console\Commands;

use App\Models\AdCampaign;
use App\Notifications\Ads\CampaignEndingSoon;
use App\Notifications\Ads\CampaignStatusChanged;
use Illuminate\Console\Command;

class ProcessCampaigns extends Command
{
    protected $signature = 'troofe:campaigns';

    protected $description = 'Fin de diffusion, bilans et alertes de fin prochaine des campagnes';

    public function handle(): int
    {
        AdCampaign::with('user')->where('status', 'active')->whereNull('ending_notice_sent_at')
            ->whereDate('ends_on', '>=', today())->whereDate('ends_on', '<=', today()->addDays(3))
            ->each(function (AdCampaign $c) {
                $c->user?->notify(new CampaignEndingSoon($c));
                $c->update(['ending_notice_sent_at' => now()]);
            });

        AdCampaign::with('user')->where('status', 'active')->whereDate('ends_on', '<', today())
            ->each(function (AdCampaign $c) {
                $c->update(['status' => 'terminee']);
                $c->user?->notify(new CampaignStatusChanged($c));
            });

        return self::SUCCESS;
    }
}
