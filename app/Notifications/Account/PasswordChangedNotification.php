<?php

namespace App\Notifications\Account;

use App\Notifications\TroofeNotification;

class PasswordChangedNotification extends TroofeNotification
{
    public function category(): string { return 'compte'; }

    public function title(): string { return __('Votre mot de passe a été modifié'); }

    public function message(): string { return __('Le mot de passe de votre compte vient d\'être modifié. Si ce n\'est pas vous, réinitialisez-le immédiatement et contactez-nous.'); }

    public function url(): string { return route('password.request'); }

    public function icon(): string { return 'bi-shield-lock'; }
}
