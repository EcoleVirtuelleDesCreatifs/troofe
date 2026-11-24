<?php

namespace Tests\Feature;

use App\Models\AdCampaign;
use App\Models\AdSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PublicSafetyTest extends TestCase
{
    use RefreshDatabase;

    private function campaign(): AdCampaign
    {
        $slot = AdSlot::create(['code' => 'home_native', 'name' => 'Test', 'page' => 'home', 'format' => 'native', 'price_per_day' => 1000]);

        return AdCampaign::create(['ad_slot_id' => $slot->id, 'advertiser' => 'Test', 'title' => 'Test', 'url' => 'https://example.com/offer', 'starts_on' => today(), 'ends_on' => today()->addWeek(), 'amount' => 7000, 'status' => 'active']);
    }

    public function test_public_language_is_determined_by_the_path_not_session_or_browser(): void
    {
        $this->withSession(['locale' => 'en'])->withHeader('Accept-Language', 'en')->get('/fr')->assertOk()->assertSee('<html lang="fr"', false)->assertHeader('Content-Language', 'fr');
        $this->withSession(['locale' => 'fr'])->get('/en')->assertOk()->assertSee('<html lang="en"', false)->assertHeader('Content-Language', 'en');
        $this->get('/')->assertStatus(301)->assertRedirect('/fr');
        $this->get('/encyclopedie?lang=en&page=2')->assertStatus(301)->assertRedirect('/en/encyclopedie?page=2');
        $this->get('/en/encyclopedie?lang=fr')->assertStatus(301)->assertRedirect('/en/encyclopedie');
    }

    public function test_localized_canonical_alternates_navigation_and_single_home_heading(): void
    {
        $this->campaign();
        $html = $this->get('/en')->assertOk()->getContent();
        $this->assertSame(1, substr_count($html, '<h1'));
        $this->assertStringContainsString('<link rel="canonical" href="'.url('/en').'"', $html);
        $this->assertStringContainsString('hreflang="fr" href="'.url('/fr').'"', $html);
        $this->assertStringContainsString('hreflang="en" href="'.url('/en').'"', $html);
        $this->assertStringContainsString('href="'.url('/en/restaurants').'"', $html);
        $this->assertStringContainsString('noopener sponsored nofollow', $html);
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /pub', false);
    }

    public function test_sponsored_hero_still_has_one_primary_heading_and_safe_links(): void
    {
        $campaign = $this->campaign();
        $campaign->slot->update(['code' => 'home_hero']);
        $html = $this->get('/fr')->assertOk()->getContent();
        $this->assertSame(1, substr_count($html, '<h1'));
        $this->assertSame(5, substr_count($html, '<h2 class="hs2-title"'));
        $this->assertStringContainsString('noopener sponsored nofollow', $html);
        $this->withSession(['locale' => 'en'])->get('/fr/encyclopedie')->assertOk()->assertSee('<html lang="fr"', false);
        $this->get('/en/encyclopedie?page=2')->assertOk()->assertSee('href="'.url('/en/encyclopedie').'?page=2"', false);
    }

    public function test_crawlers_prefetch_and_head_requests_do_not_count_clicks(): void
    {
        $campaign = $this->campaign();
        foreach ([['User-Agent' => 'Googlebot/2.1'], ['User-Agent' => 'facebookexternalhit/1.1'], ['User-Agent' => 'WhatsApp/2.0'], ['User-Agent' => 'Mozilla/5.0', 'Sec-Fetch-Mode' => 'no-cors'], ['User-Agent' => 'Mozilla/5.0', 'Purpose' => 'prefetch'], ['User-Agent' => 'Mozilla/5.0', 'Sec-Purpose' => 'prefetch;prerender'], ['User-Agent' => '']] as $headers) {
            $this->withHeaders($headers)->get('/pub/'.$campaign->id.'/clic')->assertRedirect($campaign->url);
            $this->flushHeaders();
        }
        $this->withHeader('User-Agent', 'Mozilla/5.0')->head('/pub/'.$campaign->id.'/clic')->assertRedirect($campaign->url);
        $this->assertSame(0, $campaign->fresh()->clicks);
    }

    public function test_human_clicks_are_deduplicated_for_24_hours_without_caching_redirects(): void
    {
        Cache::flush();
        $campaign = $this->campaign();
        $this->withHeader('User-Agent', 'Mozilla/5.0')->get('/pub/'.$campaign->id.'/clic')->assertRedirect($campaign->url)->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->get('/pub/'.$campaign->id.'/clic')->assertRedirect($campaign->url);
        $this->assertSame(1, $campaign->fresh()->clicks);
        $this->travel(25)->hours();
        $this->get('/pub/'.$campaign->id.'/clic')->assertRedirect($campaign->url);
        $this->assertSame(2, $campaign->fresh()->clicks);
    }
}
