<?php

namespace App\Notifications\Engagement;

use App\Notifications\TroofeNotification;

/** Aux abonnés d'une fiche : nouvel événement, nouvelle formule, nouvel article… */
class FollowedUpdate extends TroofeNotification
{
    public function __construct(public string $source, public string $headline, public string $detail, public string $link) {}

    public function category(): string { return 'abonnements'; }

    public function title(): string { return __(':source : :headline', ['source' => $this->source, 'headline' => $this->headline]); }

    public function message(): string { return $this->detail; }

    public function url(): string { return $this->link; }

    public function icon(): string { return 'bi-broadcast'; }

    protected function actionLabel(): string { return __('Découvrir'); }
}
