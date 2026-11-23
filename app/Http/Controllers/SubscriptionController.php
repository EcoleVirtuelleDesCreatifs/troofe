<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Subscription;
use App\Notifications\Billing\SubscriptionCancelled;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __construct(private PaymentService $payments) {}

    public function pricing(Request $request)
    {
        seo()->title(__('Formules pro : restaurants, artistes, organisateurs'))->description(__('Publiez votre restaurant, votre troupe ou votre atelier sur Trôofê : gratuit pour commencer, paiement Mobile Money, sans engagement.'))->faq([
            [__('Puis-je commencer gratuitement ?'), __('Oui : la formule Découverte permet de publier une fiche et de recevoir commandes et réservations, avec une commission sur les ventes.')],
            [__('Comment payer mon abonnement ?'), __('Par Orange Money, MTN MoMo, Moov Money, Wave ou carte bancaire. La facture PDF est envoyée par e-mail.')],
            [__('Que se passe-t-il à l\'échéance ?'), __('Vous êtes prévenu par e-mail 7, 3 et 1 jour avant, puis vous disposez de 3 jours de grâce. Rien n\'est supprimé : les fiches au-delà de la limite gratuite sont simplement masquées.')],
            [__('Puis-je résilier ?'), __('Oui, en un clic depuis votre espace. La formule reste active jusqu\'à la fin de la période payée.')],
        ]);

        return view('pages.pricing', [
            'plans' => Plan::where('is_active', true)->orderBy('position')->get(),
            'current' => $request->user()?->activeSubscription,
        ]);
    }

    public function subscribe(Request $request, Plan $plan)
    {
        abort_unless($plan->is_active, 404);
        $cycle = $request->validate(['cycle' => 'required|in:monthly,yearly'])['cycle'];
        $user = $request->user();

        if ($plan->isFree()) {
            return redirect()->route('partner')->with('success', __('La formule Découverte est déjà active sur votre compte.'));
        }

        // Une souscription en attente pour la même formule est réutilisée
        $subscription = Subscription::firstOrCreate(
            ['user_id' => $user->id, 'plan_id' => $plan->id, 'status' => 'pending'],
            ['billing_cycle' => $cycle, 'auto_renew' => true],
        );
        $subscription->update(['billing_cycle' => $cycle]);

        return redirect()->away($this->payments->start($user, $subscription, $plan->price($cycle),
            __('Abonnement :plan (:cycle)', ['plan' => $plan->tr('name'), 'cycle' => __($subscription->cycleLabel())])));
    }

    public function renew(Request $request, Subscription $subscription)
    {
        abort_unless($subscription->user_id === $request->user()->id, 403);
        abort_if($subscription->status === 'expired' && $subscription->ends_at?->lt(now()->subDays(60)), 410);

        return redirect()->away($this->payments->start($request->user(), $subscription, $subscription->amount(),
            __('Renouvellement :plan (:cycle)', ['plan' => $subscription->plan->tr('name'), 'cycle' => __($subscription->cycleLabel())])));
    }

    public function cancel(Request $request, Subscription $subscription)
    {
        abort_unless($subscription->user_id === $request->user()->id && in_array($subscription->status, ['active', 'past_due'], true), 403);
        $subscription->update(['auto_renew' => false, 'cancelled_at' => now(), 'status' => 'cancelled']);
        $request->user()->notify(new SubscriptionCancelled($subscription));

        return back()->with('success', __('Résiliation enregistrée. Votre formule reste active jusqu\'au :date.', ['date' => date_human($subscription->ends_at)]));
    }

    public function resume(Request $request, Subscription $subscription)
    {
        abort_unless($subscription->user_id === $request->user()->id && $subscription->status === 'cancelled' && $subscription->ends_at?->isFuture(), 403);
        $subscription->update(['auto_renew' => true, 'cancelled_at' => null, 'status' => 'active']);

        return back()->with('success', __('Votre abonnement est réactivé.'));
    }
}
