<?php

namespace App\Models;

use App\Notifications\Account\ResetPasswordNotification;
use App\Notifications\Account\VerifyEmailNotification;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail, FilamentUser, HasName, HasLocalePreference
{
    use HasFactory, Notifiable;

    public const ROLES = [
        'membre' => 'Membre',
        'restaurateur' => 'Restaurateur',
        'organisateur' => 'Organisateur d\'événements',
        'artiste' => 'Artiste, troupe ou artisan',
        'annonceur' => 'Annonceur',
        'admin' => 'Administrateur',
    ];

    public const PRO_ROLES = ['restaurateur', 'organisateur', 'artiste', 'annonceur'];

    protected $fillable = [
        'name', 'email', 'password', 'phone', 'city', 'country', 'avatar', 'locale',
        'roles', 'notification_settings', 'is_active', 'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'roles' => 'array',
            'notification_settings' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /* ---------- Rôles ---------- */
    public function hasRole(string ...$roles): bool
    {
        return (bool) array_intersect($roles, $this->roles ?? []);
    }

    public function addRole(string $role): void
    {
        $this->roles = array_values(array_unique([...($this->roles ?? []), $role]));
        $this->save();
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isPro(): bool
    {
        return $this->hasRole(...self::PRO_ROLES);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if (! $this->is_active) {
            return false;
        }

        return match ($panel->getId()) {
            'admin' => $this->isAdmin(),
            'pro' => $this->isPro() || $this->isAdmin(),
            default => false,
        };
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }

    public function initials(): string
    {
        return collect(explode(' ', $this->name))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
    }

    /** Les notifications partent dans la langue choisie par l'utilisateur */
    public function preferredLocale(): string
    {
        return in_array($this->locale, ['fr', 'en'], true) ? $this->locale : 'fr';
    }

    /* ---------- Notifications ---------- */
    public function wantsMail(string $category): bool
    {
        $cat = config("troofe.notification_categories.$category");
        if (! $cat || $cat['mandatory']) {
            return true;
        }

        return (bool) ($this->notification_settings[$category] ?? true);
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /* ---------- Abonnement ---------- */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class)->latest();
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->whereIn('status', ['active', 'past_due'])
            ->latestOfMany('ends_at');
    }

    public function currentPlan(): Plan
    {
        return $this->activeSubscription?->plan ?? Plan::free();
    }

    public function listingsCount(): int
    {
        return $this->restaurants()->whereIn('status', ['pending', 'published'])->count()
            + $this->talents()->whereIn('status', ['pending', 'published'])->count()
            + $this->organizedEvents()->whereIn('status', ['pending', 'published'])->where('starts_at', '>=', now())->count();
    }

    public function canCreateListing(): bool
    {
        return $this->isAdmin() || $this->listingsCount() < $this->currentPlan()->max_listings;
    }

    /* ---------- Relations ---------- */
    public function restaurants(): HasMany
    {
        return $this->hasMany(Restaurant::class, 'owner_id');
    }

    public function talents(): HasMany
    {
        return $this->hasMany(Talent::class, 'owner_id');
    }

    public function organizedEvents(): HasMany
    {
        return $this->hasMany(Event::class, 'organizer_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class)->latest();
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class)->latest();
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class)->latest();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest();
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class)->latest();
    }

    public function follows(): HasMany
    {
        return $this->hasMany(Follow::class)->latest();
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class)->latest();
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(AdCampaign::class)->latest();
    }

    public static function admins()
    {
        return static::query()->where('is_active', true)->whereJsonContains('roles', 'admin')->get();
    }
}
