<?php

namespace App\Http\Controllers;

use App\Models\AdCampaign;
use App\Models\AdSlot;
use App\Models\User;
use App\Notifications\Ads\CampaignSubmitted;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

class AdController extends Controller
{
    public function __construct(private PaymentService $payments) {}

    public function index()
    {
        return view('pages.advertise', ['slots' => AdSlot::where('is_active', true)->withCount(['campaigns as live_count' => fn ($q) => $q->live()])->orderBy('price_per_day', 'desc')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'ad_slot_id' => 'required|exists:ad_slots,id',
            'advertiser' => 'required|string|max:80',
            'title' => 'required|string|max:80',
            'text' => 'nullable|string|max:200',
            'cta' => 'required|string|max:30',
            'url' => 'required|url|max:255',
            'image' => 'nullable|image|max:2048',
            'starts_on' => 'required|date|after_or_equal:today',
            'ends_on' => 'required|date|after_or_equal:starts_on',
        ]);
        $slot = AdSlot::findOrFail($data['ad_slot_id']);
        $days = Carbon::parse($data['starts_on'])->diffInDays(Carbon::parse($data['ends_on'])) + 1;
        $data['amount'] = (int) $days * $slot->price_per_day;
        $data['image'] = $request->file('image')?->store('campagnes', 'public');
        $campaign = AdCampaign::create($data + ['user_id' => $request->user()->id, 'status' => 'en_attente']);

        if (! $request->user()->hasRole('annonceur')) {
            $request->user()->addRole('annonceur');
        }
        $note = new CampaignSubmitted($campaign->load('slot'));
        Notification::send(User::admins(), $note);

        return redirect('/pro/campaigns')->with('success', __('Campagne envoyée pour validation (:amount pour :days jours). Vous serez prévenu par e-mail.', ['amount' => money($data['amount']), 'days' => $days]));
    }

    public function pay(Request $request, AdCampaign $campaign)
    {
        abort_unless($campaign->user_id === $request->user()->id && $campaign->status === 'a_payer', 403);

        return redirect()->away($this->payments->start($request->user(), $campaign, $campaign->amount, __('Campagne « :title » — :slot', ['title' => $campaign->title, 'slot' => $campaign->slot->name])));
    }

    public function click(AdCampaign $campaign)
    {
        $campaign->increment('clicks');

        return redirect()->away($campaign->url);
    }
}
