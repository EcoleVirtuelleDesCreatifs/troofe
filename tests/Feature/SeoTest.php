<?php

namespace Tests\Feature;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    private function jsonLd(string $html): array
    {
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
        $types = [];
        foreach ($m[1] as $json) {
            $data = json_decode($json, true);
            $this->assertNotNull($data, 'JSON-LD invalide');
            foreach ($data['@graph'] ?? [$data] as $item) {
                $types = array_merge($types, (array) ($item['@type'] ?? []));
            }
        }

        return $types;
    }

    public function test_home_has_complete_head_in_both_languages(): void
    {
        $fr = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('<link rel="canonical" href="'.url('/').'"', $fr);
        $this->assertStringContainsString('hreflang="en"', $fr);
        $this->assertStringContainsString('hreflang="x-default"', $fr);
        $this->assertStringContainsString('<meta name="description"', $fr);
        $this->assertStringContainsString('index,follow', $fr);
        $this->assertStringNotContainsString('og:image" content="'.url('/').'/assets/img/illustration', $fr);
        $types = $this->jsonLd($fr);
        $this->assertContains('Organization', $types);
        $this->assertContains('WebSite', $types);

        $en = $this->get('/?lang=en')->assertOk()->getContent();
        $this->assertStringContainsString('<html lang="en"', $en);
        $this->assertStringContainsString('<link rel="canonical" href="'.url('/').'?lang=en"', $en);
    }

    public function test_filtered_and_private_pages_are_noindex(): void
    {
        $this->assertStringContainsString('noindex', $this->get('/recherche?q=attieke')->getContent());
        $this->assertStringContainsString('noindex', $this->get('/restaurants?city=Abidjan&sort=note')->getContent());
        $this->assertStringContainsString('noindex', $this->get('/connexion')->getContent());

        $user = User::factory()->create();
        $this->assertStringContainsString('noindex', $this->actingAs($user)->get('/compte')->getContent());
    }

    public function test_restaurant_page_exposes_restaurant_schema_and_faq(): void
    {
        $this->seed();
        $r = Restaurant::where('status', 'published')->firstOrFail();
        $html = $this->get(route('restaurants.show', $r))->assertOk()->getContent();
        $types = $this->jsonLd($html);
        $this->assertContains('Restaurant', $types);
        $this->assertContains('BreadcrumbList', $types);
        $this->assertContains('FAQPage', $types);
        $this->assertSame(1, substr_count($html, '<h1'));
    }

    public function test_robots_txt_allows_ai_crawlers_and_points_to_sitemap(): void
    {
        $body = $this->get('/robots.txt')->assertOk()->assertHeader('Content-Type', 'text/plain; charset=UTF-8')->getContent();
        $this->assertStringContainsString('Sitemap: '.url('/sitemap.xml'), $body);
        $this->assertStringContainsString('User-agent: GPTBot', $body);
        $this->assertStringContainsString('User-agent: ClaudeBot', $body);
        $this->assertStringContainsString('Disallow: /compte', $body);
    }

    public function test_sitemaps_are_valid_xml(): void
    {
        $this->seed();
        $index = simplexml_load_string($this->get('/sitemap.xml')->assertOk()->getContent());
        $this->assertNotFalse($index);
        $this->assertGreaterThan(3, count($index->sitemap));

        $xml = $this->get('/sitemaps/restaurants.xml')->assertOk()->getContent();
        $this->assertNotFalse(simplexml_load_string($xml));
        $this->assertStringContainsString('hreflang="en"', $xml);
        $this->get('/sitemaps/inconnu.xml')->assertNotFound();
    }

    public function test_llms_txt_and_feed(): void
    {
        $this->seed();
        $llms = $this->get('/llms.txt')->assertOk()->getContent();
        $this->assertStringStartsWith('# ', ltrim($llms));
        $this->assertStringContainsString('/llms-full.txt', $llms);
        $this->get('/llms-full.txt')->assertOk();
        $this->assertNotFalse(simplexml_load_string($this->get('/feed.xml')->assertOk()->getContent()));
    }
}
