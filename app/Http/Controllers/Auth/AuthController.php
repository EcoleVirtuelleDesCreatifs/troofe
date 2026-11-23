<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use App\Notifications\Account\WelcomeNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function loginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate(['email' => 'required|email', 'password' => 'required|string']);

        if (! Auth::attempt($credentials + ['is_active' => true], $request->boolean('remember'))) {
            return back()->withErrors(['email' => __('Adresse e-mail ou mot de passe incorrect.')])->onlyInput('email');
        }
        $request->session()->regenerate();
        $user = $request->user();
        $user->forceFill(['last_login_at' => now()])->save();
        if ($user->locale) {
            $request->session()->put('locale', $user->locale);
        }

        return redirect()->intended($user->isAdmin() ? '/admin' : route('account.dashboard'));
    }

    public function registerForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:80',
            'email' => 'required|email:rfc|max:160|unique:users,email',
            'phone' => 'nullable|string|max:40',
            'city' => 'nullable|string|max:80',
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'pro_role' => 'nullable|in:restaurateur,organisateur,artiste,annonceur',
            'newsletter' => 'nullable|boolean',
            'terms' => 'accepted',
        ]);

        $user = User::create([
            'name' => $data['name'], 'email' => mb_strtolower($data['email']), 'password' => $data['password'],
            'phone' => $data['phone'] ?? null, 'city' => $data['city'] ?? null, 'locale' => app()->getLocale(),
            'roles' => array_values(array_filter(['membre', $data['pro_role'] ?? null])),
            'notification_settings' => array_fill_keys(array_keys(config('troofe.notification_categories')), true),
        ]);

        if (! empty($data['newsletter'])) {
            // Confirmée en même temps que l'adresse du compte
            NewsletterSubscriber::updateOrCreate(['email' => $user->email], ['name' => $user->name, 'user_id' => $user->id, 'locale' => $user->locale, 'source' => 'inscription', 'topics' => array_keys(config('troofe.newsletter_topics'))]);
        }

        event(new Registered($user)); // envoie l'e-mail de vérification
        Auth::login($user);

        return redirect()->route('verification.notice');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    public function verifyNotice(Request $request)
    {
        return $request->user()->hasVerifiedEmail() ? redirect()->route('account.dashboard') : view('auth.verify');
    }

    public function verify(Request $request, string $id, string $hash)
    {
        $user = $request->user();
        abort_unless((string) $user->getKey() === $id && hash_equals(sha1($user->getEmailForVerification()), $hash), 403);

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            NewsletterSubscriber::where('email', $user->email)->whereNull('confirmed_at')->update(['confirmed_at' => now()]);
            $user->notify(new WelcomeNotification);
        }

        return redirect()->intended(route('account.dashboard'))->with('success', __('Adresse confirmée. Bienvenue sur Trôofê !'));
    }

    public function resendVerification(Request $request)
    {
        $request->user()->sendEmailVerificationNotification();

        return back()->with('success', __('Un nouveau lien vient de vous être envoyé.'));
    }
}
