<?php

namespace App\Notifications\Account;

use App\Models\User;
use App\Notifications\TroofeNotification;

class RoleGrantedNotification extends TroofeNotification
{
    public function __construct(public string $role) {}

    public function category(): string { return 'compte'; }

    public function title(): string { return __('Votre espace professionnel est ouvert'); }

    public function message(): string { return __('Le profil « :role » a été activé sur votre compte. Vous pouvez créer et gérer vos fiches depuis l\'espace pro.', ['role' => __(User::ROLES[$this->role] ?? $this->role)]); }

    public function url(): string { return url('/pro'); }

    public function icon(): string { return 'bi-briefcase'; }
}
