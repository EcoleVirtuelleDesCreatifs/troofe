<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use App\Models\Order;
use App\Models\Payment;
use App\Notifications\Account\PasswordChangedNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    public function dashboard(Request $request)
    {
        $u = $request->user();

        return view('account.dashboard', [
            'orders' => $u->orders()->with('restaurant', 'event')->take(4)->get(),
            'tickets' => $u->tickets()->with('type.event')->whereHas('type.event', fn ($q) => $q->where('starts_at', '>=', now()))->take(4)->get(),
            'bookings' => $u->bookings()->with('talent')->take(4)->get(),
            'notifications' => $u->notifications()->take(6)->get(),
            'subscription' => $u->activeSubscription?->load('plan'),
        ]);
    }

    public function profile()
    {
        return view('account.profile');
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => 'required|string|max:80',
            'email' => ['required', 'email:rfc', 'max:160', Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|string|max:40',
            'city' => 'nullable|string|max:80',
            'country' => 'nullable|string|max:80',
            'locale' => 'required|in:fr,en',
            'avatar' => 'nullable|image|max:1024',
        ]);
        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }
        $emailChanged = mb_strtolower($data['email']) !== $user->email;
        $user->fill($data);
        if ($emailChanged) {
            $user->email = mb_strtolower($data['email']);
            $user->email_verified_at = null;
        }
        $user->save();
        $request->session()->put('locale', $user->locale);
        if ($emailChanged) {
            $user->sendEmailVerificationNotification();

            return redirect()->route('verification.notice');
        }

        return back()->with('success', __('Profil enregistré.'));
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);
        $request->user()->update(['password' => $request->password]);
        Auth::logoutOtherDevices($request->password);
        $request->user()->notify(new PasswordChangedNotification);

        return back()->with('success', __('Mot de passe modifié. Vos autres sessions ont été déconnectées.'));
    }

    public function destroy(Request $request)
    {
        $request->validate(['password_delete' => 'required|current_password']);
        $user = $request->user();
        abort_if($user->isAdmin(), 403);
        NewsletterSubscriber::where('email', $user->email)->delete();
        Auth::logout();
        $user->delete();
        $request->session()->invalidate();

        return redirect()->route('home')->with('success', __('Votre compte et vos données ont été supprimés.'));
    }

    public function notifications(Request $request)
    {
        return view('account.notifications', ['notifications' => $request->user()->notifications()->paginate(20)]);
    }

    public function openNotification(Request $request, string $id)
    {
        $n = $request->user()->notifications()->findOrFail($id);
        $n->markAsRead();

        return redirect($n->data['url'] ?? route('account.notifications'));
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }

    public function updatePreferences(Request $request)
    {
        $cats = array_keys(config('troofe.notification_categories'));
        $request->user()->update(['notification_settings' => collect($cats)->mapWithKeys(fn ($c) => [$c => $request->boolean("mail.$c")])->all()]);

        return back()->with('success', __('Préférences de notification enregistrées.'));
    }

    public function orders(Request $request)
    {
        return view('account.orders', ['orders' => $request->user()->orders()->with('restaurant', 'event', 'items')->paginate(10)]);
    }

    public function order(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        return view('account.order', ['order' => $order->load('items', 'restaurant', 'event', 'tickets.type', 'payments')]);
    }

    public function tickets(Request $request)
    {
        $tickets = $request->user()->tickets()->with('type.event')->get()->sortBy(fn ($t) => $t->type->event->starts_at);
        [$upcoming, $past] = $tickets->partition(fn ($t) => $t->type->event->starts_at->gte(now()->startOfDay()));

        return view('account.tickets', compact('upcoming', 'past'));
    }

    public function bookings(Request $request)
    {
        $u = $request->user();

        return view('account.bookings', [
            'mine' => $u->bookings()->with('talent', 'offer')->get(),
            'received' => \App\Models\Booking::whereIn('talent_id', $u->talents()->select('id'))->with('talent', 'offer', 'user')->latest()->get(),
        ]);
    }

    public function favorites(Request $request)
    {
        return view('account.favorites', ['favorites' => $request->user()->favorites()->with('favoritable')->get()->filter(fn ($f) => $f->favoritable)]);
    }

    public function follows(Request $request)
    {
        $u = $request->user();

        return view('account.follows', [
            'follows' => $u->follows()->with('followable')->get()->filter(fn ($f) => $f->followable),
            'newsletter' => NewsletterSubscriber::where('email', $u->email)->first(),
        ]);
    }

    public function billing(Request $request)
    {
        $u = $request->user();

        return view('account.billing', [
            'subscription' => $u->activeSubscription?->load('plan'),
            'history' => $u->subscriptions()->with('plan')->get(),
            'payments' => $u->payments()->latest()->paginate(15),
            'plan' => $u->currentPlan(),
            'used' => $u->listingsCount(),
        ]);
    }

    public function invoice(Request $request, Payment $payment)
    {
        abort_unless(($payment->user_id === $request->user()->id || $request->user()->isAdmin()) && $payment->isPaid(), 404);
        $payment->load('user', 'payable');

        return Pdf::loadView('account.invoice-pdf', compact('payment'))->download('facture-'.$payment->invoice_number.'.pdf');
    }
}
