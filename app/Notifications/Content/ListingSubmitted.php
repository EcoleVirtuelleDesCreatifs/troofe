<?php

namespace App\Notifications\Content;

use App\Notifications\TroofeNotification;
use Illuminate\Database\Eloquent\Model;

/** Aux administrateurs : fiche à valider */
class ListingSubmitted extends TroofeNotification
{
    public function __construct(public Model $listing, public string $kind) {}

    public function category(): string { return 'moderation'; }

    public function title(): string { return __('À valider : :kind « :name »', ['kind' => __($this->kind), 'name' => $this->listing->name ?? $this->listing->title]); }

    public function message(): string { return __('Soumis par :owner.', ['owner' => $this->listing->owner?->name ?? $this->listing->organizer?->name ?? '—']); }

    public function url(): string { return url('/admin'); }

    public function icon(): string { return 'bi-hourglass-split'; }

    protected function actionLabel(): string { return __('Ouvrir l\'administration'); }
}
