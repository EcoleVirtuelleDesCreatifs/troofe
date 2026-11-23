<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\Account\PasswordChangedNotification;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class PasswordController extends Controller
{
    public function requestForm()
    {
        return view('auth.forgot');
    }

    public function sendLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        Password::sendResetLink($request->only('email'));

        // Même réponse que l'adresse existe ou non (pas d'énumération de comptes)
        return back()->with('success', __('Si un compte existe pour cette adresse, un lien de réinitialisation vient d\'être envoyé.'));
    }

    public function resetForm(Request $request, string $token)
    {
        return view('auth.reset', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ]);

        $status = Password::reset($request->only('email', 'password', 'password_confirmation', 'token'), function (User $user, string $password) {
            $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($user));
            $user->notify(new PasswordChangedNotification);
        });

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('success', __('Mot de passe modifié. Vous pouvez vous connecter.'))
            : back()->withErrors(['email' => __('Ce lien est invalide ou a expiré. Faites une nouvelle demande.')]);
    }
}
