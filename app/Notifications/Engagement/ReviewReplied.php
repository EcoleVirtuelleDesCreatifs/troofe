<?php

namespace App\Notifications\Engagement;

use App\Models\Review;
use App\Notifications\TroofeNotification;

class ReviewReplied extends TroofeNotification
{
    public function __construct(public Review $review, public string $listingName, public string $listingUrl) {}

    public function category(): string { return 'avis'; }

    public function title(): string { return __(':name a répondu à votre avis', ['name' => $this->listingName]); }

    public function message(): string { return '« '.\Illuminate\Support\Str::limit($this->review->reply, 200).' »'; }

    public function url(): string { return $this->listingUrl.'#avis'; }

    public function icon(): string { return 'bi-reply'; }
}
