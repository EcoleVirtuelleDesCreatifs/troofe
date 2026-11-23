<?php

namespace App\Notifications\Content;

use App\Notifications\TroofeNotification;
use Illuminate\Database\Eloquent\Model;

/** Au propriétaire : fiche publiée, refusée ou suspendue */
class ListingModerated extends TroofeNotification
{
    public function __construct(public Model $listing, public string $status, public ?string $note = null, public ?string $publicUrl = null) {}

    public function category(): string { return 'moderation'; }

    public function title(): string
    {
        $name = $this->listing->name ?? $this->listing->title;

        return match ($this->status) {
            'published' => __('« :name » est en ligne', ['name' => $name]),
            'rejected' => __('« :name » n\'a pas été validée', ['name' => $name]),
            'suspended' => __('« :name » est suspendue', ['name' => $name]),
            default => __('« :name » : statut mis à jour', ['name' => $name]),
        };
    }

    public function message(): string
    {
        $base = match ($this->status) {
            'published' => __('Votre fiche a été validée par l\'équipe et est désormais visible du public.'),
            'rejected' => __('Votre fiche doit être complétée avant publication.'),
            'suspended' => __('Votre fiche n\'est plus visible du public.'),
            default => '',
        };

        return trim($base.($this->note ? ' '.__('Motif : :note', ['note' => $this->note]) : ''));
    }

    public function url(): string { return $this->status === 'published' && $this->publicUrl ? $this->publicUrl : url('/pro'); }

    public function icon(): string { return $this->status === 'published' ? 'bi-check2-circle' : 'bi-info-circle'; }
}
