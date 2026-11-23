<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use App\Notifications\Newsletter\ConfirmSubscription;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:160'],
            'name' => ['nullable', 'string', 'max:80'],
            'topics' => ['nullable', 'array'],
            'topics.*' => [Rule::in(array_keys(config('troofe.newsletter_topics')))],
            'website' => ['prohibited'], // pot de miel anti-robots
        ]);

        $sub = NewsletterSubscriber::firstOrNew(['email' => mb_strtolower($data['email'])]);
        $sub->fill([
            'name' => $data['name'] ?? $sub->name,
            'topics' => $data['topics'] ?? $sub->topics ?? array_keys(config('troofe.newsletter_topics')),
            'user_id' => $request->user()?->id ?? $sub->user_id,
            'locale' => app()->getLocale(),
            'source' => $sub->source ?? 'site',
            'unsubscribed_at' => null,
        ]);
        $alreadyActive = $sub->exists && $sub->confirmed_at;
        $sub->save();

        // Membre connecté avec cette adresse vérifiée : pas besoin de reconfirmer
        if (! $alreadyActive && $request->user()?->hasVerifiedEmail() && $request->user()->email === $sub->email) {
            $sub->update(['confirmed_at' => now()]);

            return back()->with('success', __('Vous êtes inscrit à la lettre d\'information.'));
        }
        if (! $alreadyActive) {
            $sub->notify(new ConfirmSubscription);
        }

        return back()->with('success', $alreadyActive ? __('Vos préférences ont été mises à jour.') : __('Presque fini ! Cliquez sur le lien reçu par e-mail pour confirmer votre inscription.'));
    }

    public function confirm(string $token)
    {
        $sub = NewsletterSubscriber::where('token', $token)->firstOrFail();
        $sub->update(['confirmed_at' => $sub->confirmed_at ?? now(), 'unsubscribed_at' => null]);

        return view('pages.newsletter', ['sub' => $sub, 'state' => 'confirmed']);
    }

    public function manage(string $token)
    {
        return view('pages.newsletter', ['sub' => NewsletterSubscriber::where('token', $token)->firstOrFail(), 'state' => 'manage']);
    }

    public function update(Request $request, string $token)
    {
        $sub = NewsletterSubscriber::where('token', $token)->firstOrFail();
        $data = $request->validate([
            'topics' => ['nullable', 'array'],
            'topics.*' => [Rule::in(array_keys(config('troofe.newsletter_topics')))],
            'frequency' => ['required', 'in:weekly,monthly'],
            'locale' => ['required', 'in:fr,en'],
        ]);
        $sub->update(['topics' => $data['topics'] ?? [], 'frequency' => $data['frequency'], 'locale' => $data['locale']]);

        return back()->with('success', __('Préférences enregistrées.'));
    }

    /** Désinscription en un clic (lien e-mail et en-tête List-Unsubscribe) */
    public function unsubscribe(string $token)
    {
        $sub = NewsletterSubscriber::where('token', $token)->firstOrFail();
        $sub->update(['unsubscribed_at' => now()]);

        return request()->isMethod('post') ? response('OK') : view('pages.newsletter', ['sub' => $sub, 'state' => 'unsubscribed']);
    }
}
