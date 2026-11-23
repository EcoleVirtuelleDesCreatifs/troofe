<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Models\Plan;
use App\Models\User;
use App\Notifications\Account\RoleGrantedNotification;
use App\Notifications\Engagement\ContactMessageReceived;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

class ContactController extends Controller
{
    public function create()
    {
        return view('pages.contact');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:80',
            'email' => 'required|email|max:160',
            'subject' => 'required|string|max:120',
            'message' => 'required|string|min:10|max:4000',
            'website' => 'prohibited',
        ]);
        $msg = ContactMessage::create($data + ['user_id' => $request->user()?->id]);
        Notification::route('mail', config('troofe.admin_email'))->notify(new ContactMessageReceived($msg));

        return back()->with('success', __('Message envoyé. Nous vous répondons sous 48 h ouvrées.'));
    }

    public function partner()
    {
        return view('pages.partner', ['plans' => Plan::where('is_active', true)->orderBy('position')->get()]);
    }

    /** Ouvre l'espace pro : le rôle est accordé immédiatement, les fiches restent soumises à validation */
    public function becomePartner(Request $request)
    {
        $role = $request->validate(['role' => ['required', Rule::in(User::PRO_ROLES)]])['role'];
        $user = $request->user();
        if (! $user->hasRole($role)) {
            $user->addRole($role);
            $user->notify(new RoleGrantedNotification($role));
        }

        return redirect('/pro')->with('success', __('Votre espace pro est ouvert. Créez votre première fiche.'));
    }
}
