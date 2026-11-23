<?php

namespace App\Notifications\Engagement;

use App\Models\Review;
use App\Notifications\TroofeNotification;

class NewReviewReceived extends TroofeNotification
{
    public function __construct(public Review $review, public string $listingName, public string $listingUrl) {}

    public function category(): string { return 'avis'; }

    public function title(): string { return __('Nouvel avis :stars sur « :name »', ['stars' => str_repeat('★', $this->review->rating), 'name' => $this->listingName]); }

    public function message(): string { return __(':user : « :text »', ['user' => $this->review->user->name, 'text' => \Illuminate\Support\Str::limit($this->review->body, 160)]); }

    public function url(): string { return $this->listingUrl.'#avis'; }

    public function icon(): string { return 'bi-star'; }

    protected function actionLabel(): string { return __('Lire et répondre'); }
}
