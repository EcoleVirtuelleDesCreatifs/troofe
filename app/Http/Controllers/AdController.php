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
        seo()->title(__('Publicité : annoncer sur Trôofê'))->description(__('Bannières, cartes sponsorisées et encarts sur les pages restaurants, culture et agenda. Tarifs à la journée, paiement Mobile Money.'));

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

    public function click(Request $request, AdCampaign $campaign)
    {
        $agent = $request->userAgent() ?? '';
        $purpose = implode(' ', array_map(fn ($header) => $request->header($header, ''), ['Purpose', 'Sec-Purpose', 'X-Purpose', 'X-Moz']));
        $automated = $agent === '' || preg_match('/bot|crawl|spider|slurp|preview|facebookexternalhit|whatsapp|telegram|embedly|iframely|headless|lighthouse|inspectiontool|pagespeed|curl|wget|python|httpclient|validator|monitor|pingdom|uptime/i', $agent)
            || preg_match('/prefetch|prerender|preview/i', $purpose)
            || ($request->hasHeader('Sec-Fetch-Mode') && $request->header('Sec-Fetch-Mode') !== 'navigate');
        $live = $campaign->status === 'active' && $campaign->starts_on->lte(today()) && $campaign->ends_on->gte(today()) && $campaign->slot?->is_active;

        if ($request->isMethod('GET') && ! $automated && $live) {
            $prefix = 'ad-click:'.$campaign->id.':';
            $ip = hash_hmac('sha256', (string) $request->ip(), config('app.key'));
            $session = hash_hmac('sha256', $request->session()->getId(), config('app.key'));
            $expires = now()->addDay();
            if (\Illuminate\Support\Facades\Cache::add($prefix.'ip:'.$ip, true, $expires)
                && \Illuminate\Support\Facades\Cache::add($prefix.'session:'.$session, true, $expires)) {
                $campaign->increment('clicks');
            }
        }

        return redirect()->away($campaign->url)->withHeaders([
            'Cache-Control' => 'no-store, private',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }
}
