<?php

namespace App\Models;

use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class NewsletterSubscriber extends Model implements HasLocalePreference
{
    use \Illuminate\Notifications\Notifiable;

    public function routeNotificationForMail(): string
    {
        return $this->email;
    }

    public function preferredLocale(): string
    {
        return $this->locale ?: 'fr';
    }

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'topics' => 'array',
            'confirmed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
            'last_sent_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn ($s) => $s->token ??= Str::random(48));
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->whereNotNull('confirmed_at')->whereNull('unsubscribed_at');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->confirmed_at && ! $this->unsubscribed_at;
    }

    public function wants(string $topic): bool
    {
        return empty($this->topics) || in_array($topic, $this->topics, true);
    }
}
