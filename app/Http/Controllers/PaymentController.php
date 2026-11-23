<?php

namespace App\Http\Controllers;

use App\Models\AdCampaign;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Subscription;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $payments) {}

    /** Page de test (pilote « simulation » uniquement) */
    public function simulate(Request $request, Payment $payment)
    {
        abort_unless($payment->user_id === $request->user()->id && $payment->provider === 'simulation' && $payment->status === 'pending', 404);

        return view('pages.payment-simulate', compact('payment'));
    }

    public function decide(Request $request, Payment $payment)
    {
        abort_unless($payment->user_id === $request->user()->id && $payment->provider === 'simulation' && $payment->status === 'pending', 404);
        $data = $request->validate(['decision' => 'required|in:paid,failed', 'method' => 'nullable|in:OM,MOMO,MOOV,WAVE,CARD']);
        $payment->update(['provider_payload' => ['simulation' => $data['decision'], 'method' => $data['method'] ?? 'OM']]);
        $this->payments->sync($payment);

        return redirect()->route('payments.return', $payment);
    }

    /** Retour du client après paiement : on vérifie toujours auprès du prestataire */
    public function return(Request $request, Payment $payment)
    {
        if ($payment->status === 'pending') {
            try {
                $payment = $this->payments->sync($payment);
            } catch (\Throwable $e) {
                Log::warning('Vérification paiement impossible', ['ref' => $payment->reference, 'error' => $e->getMessage()]);
            }
        }

        $next = match (true) {
            $payment->payable instanceof Subscription => route('account.billing'),
            $payment->payable instanceof Order && $payment->payable->type === 'billets' => route('account.tickets'),
            $payment->payable instanceof Order => route('account.orders.show', $payment->payable),
            $payment->payable instanceof AdCampaign => url('/pro/campaigns'),
            default => route('account.dashboard'),
        };

        return view('pages.payment-result', compact('payment', 'next'));
    }

    /** Notification serveur à serveur (webhook) */
    public function notify(Request $request, string $provider)
    {
        $reference = $request->input('cpm_trans_id') ?? $request->input('transaction_id');
        $payment = $reference ? Payment::where('reference', $reference)->where('provider', $provider)->first() : null;

        if ($payment) {
            try {
                $this->payments->sync($payment);
            } catch (\Throwable $e) {
                Log::error('Webhook paiement', ['ref' => $reference, 'error' => $e->getMessage()]);

                return response('retry', 500);
            }
        }

        return response('OK');
    }

    public function retry(Request $request, Payment $payment)
    {
        abort_unless($payment->user_id === $request->user()->id && $payment->status === 'failed', 404);
        $payable = $payment->payable;

        if ($payable instanceof Order) {
            // Commande annulée et stock libéré : on renvoie vers la fiche pour recommander
            return redirect($payable->type === 'billets' ? route('events.show', $payable->event) : route('restaurants.show', $payable->restaurant))
                ->with('info', __('Votre précédente commande a été annulée. Vous pouvez la refaire en quelques secondes.'));
        }

        return redirect()->away($this->payments->start($request->user(), $payable, $payment->amount, $payment->description));
    }
}
