<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Dish;
use App\Models\Event;
use App\Models\Restaurant;
use App\Models\Talent;
use Illuminate\Support\Facades\Cache;

/**
 * Fichiers destinés aux robots : sitemaps, robots.txt, llms.txt (moteurs
 * génératifs), flux RSS et clé IndexNow.
 */
class SeoController extends Controller
{
    private const SECTIONS = ['pages', 'restaurants', 'culture', 'evenements', 'encyclopedie', 'actualites'];

    public function robots()
    {
        $sitemap = route('sitemap');
        $private = "Disallow: /admin\nDisallow: /pro\nDisallow: /compte\nDisallow: /connexion\nDisallow: /inscription\nDisallow: /paiement\nDisallow: /billet\nDisallow: /recherche\nDisallow: /controle-acces\nDisallow: /newsletter/\nDisallow: /pub\nDisallow: /fr/recherche\nDisallow: /en/recherche\nDisallow: /*?*q=\n";
        // Robots des moteurs génératifs explicitement autorisés sur le contenu public (visibilité dans ChatGPT, Claude, Perplexity, Gemini…)
        $ai = collect(['GPTBot', 'OAI-SearchBot', 'ChatGPT-User', 'ClaudeBot', 'Claude-User', 'Claude-SearchBot', 'PerplexityBot', 'Perplexity-User', 'Google-Extended', 'Applebot-Extended', 'Bingbot', 'meta-externalagent', 'cohere-ai', 'MistralAI-User', 'DuckAssistBot'])
            ->map(fn ($bot) => "User-agent: $bot\nAllow: /\n$private")->implode("\n");

        $body = "# Trôofê — ".url('/')."\n# Résumé pour les IA : ".route('llms')."\n\nUser-agent: *\nAllow: /\n$private\n$ai\nSitemap: $sitemap\n";

        return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function sitemapIndex()
    {
        $xml = Cache::remember('sitemap:v2:index', 3600, fn () => view('seo.sitemap-index', ['sections' => self::SECTIONS])->render());

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function sitemap(string $section)
    {
        abort_unless(in_array($section, self::SECTIONS, true), 404);
        $xml = Cache::remember("sitemap:v2:$section", 3600, fn () => view('seo.sitemap', ['entries' => $this->entries($section)])->render());

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    /** @return array<int, array{loc:string, lastmod:?string, image:?string, title:?string, priority:string, freq:string}> */
    private function entries(string $section): array
    {
        $img = fn (?string $url) => $url && ! str_contains($url, '/illustration/') ? $url : null;

        return match ($section) {
            'pages' => collect([[route('home'), '1.0', 'daily'], [route('restaurants.index'), '0.9', 'daily'], [route('culture.hub'), '0.9', 'weekly'], [route('events.index'), '0.9', 'daily'],
                [route('dishes.index'), '0.8', 'weekly'], [route('articles.index'), '0.8', 'daily'], [route('pricing'), '0.6', 'monthly'], [route('partner'), '0.6', 'monthly'],
                [route('advertise'), '0.5', 'monthly'], [route('contact'), '0.3', 'yearly'], [route('page', 'a-propos'), '0.4', 'yearly']])
                ->merge(collect(Talent::TYPES)->map(fn ($t) => [route('culture.index', $t['route']), '0.8', 'weekly'])->values())
                ->merge(Restaurant::published()->distinct()->pluck('city')->map(fn ($c) => [route('restaurants.index', ['city' => $c]), '0.7', 'weekly']))
                ->map(fn ($r) => ['loc' => $r[0], 'lastmod' => null, 'image' => null, 'title' => null, 'priority' => $r[1], 'freq' => $r[2]])->all(),
            'restaurants' => Restaurant::published()->get()->map(fn ($r) => ['loc' => route('restaurants.show', $r), 'lastmod' => $r->updated_at?->toAtomString(), 'image' => $img($r->coverUrl()), 'title' => $r->name, 'priority' => $r->is_featured ? '0.9' : '0.8', 'freq' => 'weekly'])->all(),
            'culture' => Talent::published()->get()->map(fn ($t) => ['loc' => $t->url(), 'lastmod' => $t->updated_at?->toAtomString(), 'image' => $img($t->coverUrl()), 'title' => $t->name, 'priority' => '0.8', 'freq' => 'weekly'])->all(),
            'evenements' => Event::published()->upcoming()->get()->map(fn ($e) => ['loc' => route('events.show', $e), 'lastmod' => $e->updated_at?->toAtomString(), 'image' => $img($e->coverUrl()), 'title' => $e->title, 'priority' => '0.8', 'freq' => 'daily'])->all(),
            'encyclopedie' => Dish::published()->get()->map(fn ($d) => ['loc' => route('dishes.show', $d), 'lastmod' => $d->updated_at?->toAtomString(), 'image' => $img($d->imageUrl()), 'title' => $d->name, 'priority' => '0.7', 'freq' => 'monthly'])->all(),
            'actualites' => Article::published()->latest('published_at')->get()->map(fn ($a) => ['loc' => route('articles.show', $a), 'lastmod' => $a->updated_at?->toAtomString(), 'image' => $img($a->coverUrl()), 'title' => $a->title, 'priority' => '0.6', 'freq' => 'monthly'])->all(),
        };
    }

    /** llms.txt : résumé structuré du site pour les moteurs génératifs (format llmstxt.org) */
    public function llms()
    {
        $txt = Cache::remember('llms:short', 3600, fn () => view('seo.llms', $this->llmsData(false))->render());

        return response($txt, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    /** llms-full.txt : faits détaillés (adresses, prix, horaires, formules) pour les réponses précises */
    public function llmsFull()
    {
        $txt = Cache::remember('llms:full', 3600, fn () => view('seo.llms', $this->llmsData(true))->render());

        return response($txt, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    private function llmsData(bool $full): array
    {
        app()->setLocale('fr');

        return [
            'full' => $full,
            'restaurants' => Restaurant::published()->with(['menuItems', 'dishes'])->orderBy('city')->orderBy('name')->get(),
            'talents' => Talent::published()->with('offers')->orderBy('type')->orderBy('name')->get()->groupBy('type'),
            'events' => Event::published()->upcoming()->with('ticketTypes')->orderBy('starts_at')->get(),
            'dishes' => Dish::published()->orderBy('name')->get(),
            'articles' => Article::published()->latest('published_at')->take($full ? 50 : 10)->get(),
        ];
    }

    public function feed()
    {
        $xml = Cache::remember('feed:'.app()->getLocale(), 1800, fn () => view('seo.feed', [
            'articles' => Article::published()->latest('published_at')->take(30)->get(),
            'events' => Event::published()->upcoming()->orderBy('starts_at')->take(20)->get(),
        ])->render());

        return response($xml, 200, ['Content-Type' => 'application/rss+xml; charset=UTF-8']);
    }

    public function indexNowKey()
    {
        abort_unless($key = config('troofe.indexnow_key'), 404);

        return response($key, 200, ['Content-Type' => 'text/plain']);
    }
}
