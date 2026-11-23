<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\Restaurant;
use App\Models\Talent;
use App\Models\User;
use App\Notifications\Content\ListingModerated;
use App\Notifications\Content\ListingSubmitted;
use App\Notifications\Engagement\FollowedUpdate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification;

class ModerationService
{
    public function kindOf(Model $m): string
    {
        return match (true) {
            $m instanceof Restaurant => 'Restaurant',
            $m instanceof Event => 'Événement',
            $m instanceof Talent => $m->meta('label'),
            default => 'Fiche',
        };
    }

    public function publicUrl(Model $m): ?string
    {
        return match (true) {
            $m instanceof Restaurant => route('restaurants.show', $m),
            $m instanceof Event => route('events.show', $m),
            $m instanceof Talent => $m->url(),
            default => null,
        };
    }

    /** Une fiche passe en attente : alerte des administrateurs (cloche + e-mail) */
    public function submitted(Model $listing): void
    {
        $note = new ListingSubmitted($listing, $this->kindOf($listing));
        Notification::send(User::admins(), $note);
        Notification::route('mail', config('troofe.admin_email'))->notify($note);
    }

    /** Décision de l'équipe, notifiée au propriétaire */
    public function decide(Model $listing, string $status, ?string $note = null): void
    {
        $wasPublished = $listing->status === 'published';
        $listing->update(['status' => $status, 'moderation_note' => $note]);
        ActivityLog::record('Modération : '.$status, $listing, $note);

        $owner = $listing->owner ?? $listing->organizer ?? null;
        $owner?->notify(new ListingModerated($listing, $status, $note, $this->publicUrl($listing)));

        if ($status === 'published' && ! $wasPublished && $listing instanceof Event) {
            $this->announceEvent($listing);
        }
    }

    /** Nouvel événement : prévenir les abonnés du restaurant, de l'organisateur et des artistes */
    public function announceEvent(Event $event): void
    {
        $sources = collect([$event->restaurant, ...$event->performers])->filter();
        $notified = collect();
        foreach ($sources as $source) {
            $recipients = $source->followers()->whereNotIn('id', $notified)->get();
            Notification::send($recipients, new FollowedUpdate(
                $source->name,
                __('nouvel événement'),
                $event->title.' — '.date_human($event->starts_at, 'D MMMM YYYY').', '.$event->city,
                route('events.show', $event),
            ));
            $notified = $notified->merge($recipients->pluck('id'));
        }
    }
}
