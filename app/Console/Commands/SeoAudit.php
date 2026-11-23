<?php

namespace App\Console\Commands;

use DOMDocument;
use DOMXPath;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Diagnostic SEO / GEO : parcourt le plan du site (FR et EN) et contrôle
 * chaque page : titre, description, h1, canonique, hreflang, robots,
 * Open Graph, données structurées, FAQ, textes alternatifs, contenu,
 * liens internes cassés, doublons ; puis robots.txt, llms.txt, flux RSS.
 *
 *   php artisan troofe:seo-audit                       (requêtes internes)
 *   php artisan troofe:seo-audit --base=https://troofe.net  (site en ligne)
 *   php artisan troofe:seo-audit --limit=10 --links --json=storage/app/seo.json
 */
class SeoAudit extends Command
{
    protected $signature = 'troofe:seo-audit {--base= : URL du site en ligne (sinon requêtes internes)} {--limit=0 : Pages max. par sitemap (0 = toutes)} {--links : Vérifier aussi les liens internes} {--no-en : Ne pas auditer la version anglaise} {--json= : Exporter le rapport en JSON}';

    protected $description = 'Diagnostic SEO / GEO complet du site';

    private const EXPECTED = [
        '#/restaurants/[^/?]+$#' => ['Restaurant', 'BreadcrumbList', 'FAQPage'],
        '#/encyclopedie/[^/?]+$#' => ['Recipe', 'BreadcrumbList', 'FAQPage'],
        '#/actualites/[^/?]+$#' => ['NewsArticle', 'BreadcrumbList'],
        '#/evenements/[^/?]+$#' => ['@event', 'BreadcrumbList', 'FAQPage'],
        '#/(danse|prestations|artisans)/[^/?]+$#' => ['@talent', 'BreadcrumbList', 'FAQPage'],
    ];

    private array $pages = [];

    private array $checkedLinks = [];

    public function handle(): int
    {
        $base = rtrim($this->option('base') ?: config('app.url'), '/');
        $this->info("Diagnostic SEO / GEO — $base");

        $global = $this->globalChecks($base);
        $urls = $this->sitemapUrls($base);
        if (! $urls) {
            $this->error('Aucune URL trouvée dans le sitemap.');

            return self::FAILURE;
        }
        $this->line(count($urls).' URL(s) dans le plan du site.');

        $bar = $this->output->createProgressBar(count($urls) * ($this->option('no-en') ? 1 : 2));
        foreach ($urls as $url) {
            foreach ($this->option('no-en') ? ['fr'] : ['fr', 'en'] as $lang) {
                $target = $lang === 'en' ? $url.(str_contains($url, '?') ? '&' : '?').'lang=en' : $url;
                $this->pages[$target] = $this->auditPage($target, $lang, $base);
                $bar->advance();
            }
        }
        $bar->finish();
        $this->newLine(2);

        $this->duplicates();
        $this->report($global);

        if ($path = $this->option('json')) {
            file_put_contents(base_path($path), json_encode(['base' => $base, 'date' => now()->toIso8601String(), 'global' => $global, 'pages' => $this->pages], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $this->info("Rapport JSON : $path");
        }

        $errors = collect($this->pages)->sum(fn ($p) => count($p['errors'])) + count($global['errors']);

        return $errors ? self::FAILURE : self::SUCCESS;
    }

    /** Requête interne (sans réseau) ou HTTP réelle */
    private function fetch(string $url): array
    {
        if ($this->option('base')) {
            try {
                $r = Http::timeout(20)->withHeaders(['User-Agent' => 'TroofeSeoAudit/1.0'])->withoutRedirecting()->get($url);

                return ['status' => $r->status(), 'body' => $r->body(), 'type' => $r->header('Content-Type'), 'location' => $r->header('Location')];
            } catch (\Throwable $e) {
                return ['status' => 0, 'body' => '', 'type' => '', 'location' => null, 'error' => $e->getMessage()];
            }
        }

        $kernel = app(Kernel::class);
        $parts = parse_url($url);
        $request = Request::create(($parts['path'] ?? '/').(isset($parts['query']) ? '?'.$parts['query'] : ''), 'GET', server: ['HTTP_HOST' => $parts['host'] ?? 'localhost', 'HTTPS' => ($parts['scheme'] ?? 'http') === 'https' ? 'on' : 'off', 'HTTP_ACCEPT_LANGUAGE' => 'fr']);
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
        app()->forgetScopedInstances();
        app('auth')->forgetGuards();

        return ['status' => $response->getStatusCode(), 'body' => (string) $response->getContent(), 'type' => $response->headers->get('Content-Type'), 'location' => $response->headers->get('Location')];
    }

    private function globalChecks(string $base): array
    {
        $errors = $warnings = $ok = [];

        $robots = $this->fetch($base.'/robots.txt');
        if ($robots['status'] !== 200) {
            $errors[] = 'robots.txt inaccessible';
        } else {
            preg_match('/^Sitemap:\s*(\S+)/mi', $robots['body'], $m);
            ! empty($m[1]) && str_starts_with($m[1], 'http') ? $ok[] = 'robots.txt déclare le sitemap (URL absolue)' : $errors[] = 'robots.txt : ligne Sitemap absente ou relative';
            preg_match('/User-agent:\s*\*\s*\n(?:Allow:[^\n]*\n)?Disallow:\s*\/\s*$/mi', $robots['body']) ? $errors[] = 'robots.txt bloque tout le site' : null;
            foreach (['GPTBot', 'ClaudeBot', 'PerplexityBot', 'Google-Extended', 'OAI-SearchBot'] as $bot) {
                str_contains($robots['body'], $bot) ? null : $warnings[] = "robots.txt : $bot non mentionné";
            }
            $ok[] = 'robots.txt : robots IA autorisés sur le contenu public';
        }

        $llms = $this->fetch($base.'/llms.txt');
        $llms['status'] === 200 && Str::startsWith(trim($llms['body']), '# ') && mb_strlen($llms['body']) > 500 ? $ok[] = 'llms.txt présent ('.number_format(mb_strlen($llms['body']), 0, ',', ' ').' caractères)' : $errors[] = 'llms.txt absent ou trop court';
        $full = $this->fetch($base.'/llms-full.txt');
        $full['status'] === 200 ? $ok[] = 'llms-full.txt présent' : $warnings[] = 'llms-full.txt absent';

        $feed = $this->fetch($base.'/feed.xml');
        $feed['status'] === 200 && @simplexml_load_string($feed['body']) ? $ok[] = 'Flux RSS valide' : $warnings[] = 'Flux RSS absent ou invalide';

        $manifest = $this->fetch($base.'/site.webmanifest');
        $manifest['status'] === 200 ? $ok[] = 'Manifeste web présent' : $warnings[] = 'site.webmanifest absent';

        $notFound = $this->fetch($base.'/page-qui-n-existe-pas-'.Str::random(6));
        $notFound['status'] === 404 ? $ok[] = 'Les pages inexistantes renvoient bien 404' : $errors[] = 'Une page inexistante ne renvoie pas 404 ('.$notFound['status'].')';

        if ($this->option('base')) {
            str_starts_with($base, 'https://') ? $ok[] = 'HTTPS' : $errors[] = 'Le site n\'est pas en HTTPS';
            $www = preg_replace('#^https://#', 'https://www.', $base);
            $w = $this->fetch($www.'/');
            in_array($w['status'], [301, 308], true) ? $ok[] = 'Redirection www → domaine principal' : $warnings[] = 'Pas de redirection permanente depuis www ('.$w['status'].')';
        }

        return compact('errors', 'warnings', 'ok');
    }

    private function sitemapUrls(string $base): array
    {
        $index = $this->fetch($base.'/sitemap.xml');
        $xml = @simplexml_load_string($index['body']);
        if (! $xml) {
            return [];
        }
        $urls = [];
        $limit = (int) $this->option('limit');
        foreach ($xml->sitemap ?? [] as $s) {
            $child = @simplexml_load_string($this->fetch((string) $s->loc)['body']);
            $n = 0;
            foreach ($child->url ?? [] as $u) {
                if ($limit && ++$n > $limit) {
                    break;
                }
                $urls[] = $this->localize((string) $u->loc, $base);
            }
        }

        return array_values(array_unique($urls));
    }

    /** En mode interne, les URL du sitemap utilisent APP_URL : on les garde telles quelles */
    private function localize(string $url, string $base): string
    {
        return $url;
    }

    private function auditPage(string $url, string $lang, string $base): array
    {
        $res = $this->fetch($url);
        $p = ['status' => $res['status'], 'lang' => $lang, 'errors' => [], 'warnings' => [], 'title' => null, 'description' => null, 'schemas' => [], 'words' => 0];

        if ($res['status'] !== 200) {
            $p['errors'][] = 'Statut HTTP '.$res['status'].($res['location'] ? ' → '.$res['location'] : '');

            return $p;
        }

        libxml_use_internal_errors(true);
        $dom = new DOMDocument;
        $dom->loadHTML('<?xml encoding="UTF-8">'.$res['body']);
        $x = new DOMXPath($dom);
        $meta = fn (string $q) => ($n = $x->query($q)->item(0)) ? trim($n->getAttribute('content') ?: $n->getAttribute('href')) : null;

        // Titre et description
        $title = trim($x->query('//title')->item(0)?->textContent ?? '');
        $p['title'] = $title;
        $len = mb_strlen($title);
        if (! $title) {
            $p['errors'][] = 'Balise <title> absente';
        } elseif ($len < 25 || $len > 70) {
            $p['warnings'][] = "Titre de $len caractères (idéal 30 à 65)";
        }
        $desc = $meta('//meta[@name="description"]');
        $p['description'] = $desc;
        $dl = mb_strlen((string) $desc);
        if (! $desc) {
            $p['errors'][] = 'Meta description absente';
        } elseif ($dl < 70 || $dl > 165) {
            $p['warnings'][] = "Description de $dl caractères (idéal 120 à 158)";
        }

        // Structure
        $h1 = $x->query('//h1')->length;
        if ($h1 !== 1) {
            $p[$h1 === 0 ? 'errors' : 'warnings'][] = "$h1 titre(s) h1 (attendu : 1)";
        }
        $htmlLang = $x->query('//html')->item(0)?->getAttribute('lang');
        if ($htmlLang !== $lang) {
            $p['errors'][] = "Attribut lang=\"$htmlLang\" au lieu de \"$lang\"";
        }

        // Canonique, hreflang, robots
        $canonical = $meta('//link[@rel="canonical"]');
        if (! $canonical || ! str_starts_with($canonical, 'http')) {
            $p['errors'][] = 'URL canonique absente ou relative';
        } elseif (rtrim($canonical, '/') !== rtrim($url, '/')) {
            $p['errors'][] = 'Canonique différente de l\'URL ('.$canonical.')';
        }
        foreach (['fr', 'en', 'x-default'] as $h) {
            if (! $meta('//link[@rel="alternate"][@hreflang="'.$h.'"]')) {
                $p['errors'][] = "hreflang $h absent";
            }
        }
        $robots = (string) $meta('//meta[@name="robots"]');
        if (str_contains($robots, 'noindex')) {
            $p['errors'][] = 'Page du sitemap marquée noindex';
        }

        // Partage social
        $og = $meta('//meta[@property="og:image"]');
        if (! $og) {
            $p['errors'][] = 'og:image absente';
        } elseif (str_ends_with($og, '.svg')) {
            $p['errors'][] = 'og:image au format SVG (non affichée par les réseaux sociaux)';
        }
        foreach (['og:title', 'og:description', 'og:url'] as $prop) {
            if (! $meta('//meta[@property="'.$prop.'"]')) {
                $p['warnings'][] = "$prop absente";
            }
        }

        // Données structurées
        $types = [];
        foreach ($x->query('//script[@type="application/ld+json"]') as $node) {
            $data = json_decode($node->textContent, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $p['errors'][] = 'JSON-LD invalide : '.json_last_error_msg();

                continue;
            }
            foreach ($data['@graph'] ?? [$data] as $item) {
                $types = array_merge($types, (array) ($item['@type'] ?? []));
                $this->validateSchema($item, $p);
            }
        }
        $p['schemas'] = array_values(array_unique($types));
        if (! in_array('Organization', $types, true) || ! in_array('WebSite', $types, true)) {
            $p['errors'][] = 'Organisation / WebSite absents du JSON-LD';
        }
        foreach (self::EXPECTED as $pattern => $expected) {
            if (preg_match($pattern, parse_url($url, PHP_URL_PATH) ?? '')) {
                foreach ($expected as $type) {
                    $alt = match ($type) { '@event' => ['FoodEvent', 'Festival', 'MusicEvent', 'EducationEvent', 'Event'], '@talent' => ['PerformingGroup', 'Person', 'LocalBusiness'], default => [$type] };
                    if (! array_intersect($alt, $types)) {
                        $p[$type === 'FAQPage' ? 'warnings' : 'errors'][] = 'Donnée structurée '.($type[0] === '@' ? implode('/', $alt) : $type).' manquante';
                    }
                }
            }
        }

        // Accessibilité des images et contenu
        $noAlt = 0;
        foreach ($x->query('//main//img') as $img) {
            if (! $img->hasAttribute('alt')) {
                $noAlt++;
            }
        }
        if ($noAlt) {
            $p['warnings'][] = "$noAlt image(s) sans attribut alt";
        }
        $main = $x->query('//main')->item(0);
        $p['words'] = $main ? str_word_count(preg_replace('/\s+/u', ' ', $main->textContent), 0, 'àâäéèêëîïôöùûüçœÀÂÉÈÊËÎÏÔÖÙÛÜÇŒ') : 0;
        if ($p['words'] < 150) {
            $p['warnings'][] = 'Contenu léger ('.$p['words'].' mots)';
        }

        // Liens internes
        if ($this->option('links')) {
            foreach ($x->query('//main//a[@href]') as $a) {
                $href = $a->getAttribute('href');
                if (! str_starts_with($href, $base) || str_contains($href, '#') || isset($this->checkedLinks[$href])) {
                    continue;
                }
                $status = $this->fetch($href)['status'];
                $this->checkedLinks[$href] = $status;
                if ($status >= 400) {
                    $p['errors'][] = "Lien cassé ($status) : $href";
                }
            }
        }

        return $p;
    }

    /** Champs recommandés par Google pour les résultats enrichis */
    private function validateSchema(array $item, array &$p): void
    {
        $type = (array) ($item['@type'] ?? []);
        $required = match (true) {
            in_array('Restaurant', $type) => ['name', 'address', 'servesCuisine', 'image'],
            in_array('Recipe', $type) => ['name', 'image', 'recipeIngredient'],
            (bool) array_intersect(['FoodEvent', 'Festival', 'MusicEvent', 'EducationEvent'], $type) => ['name', 'startDate', 'location', 'offers', 'image'],
            in_array('NewsArticle', $type) => ['headline', 'image', 'datePublished', 'author'],
            in_array('FAQPage', $type) => ['mainEntity'],
            in_array('BreadcrumbList', $type) => ['itemListElement'],
            default => [],
        };
        foreach ($required as $field) {
            if (empty($item[$field])) {
                $p['warnings'][] = implode('/', $type)." : champ « $field » manquant";
            }
        }
        if (isset($item['aggregateRating']) && empty($item['aggregateRating']['reviewCount'])) {
            $p['errors'][] = 'aggregateRating sans reviewCount';
        }
    }

    private function duplicates(): void
    {
        foreach (['title' => 'Titre', 'description' => 'Description'] as $field => $label) {
            collect($this->pages)->filter(fn ($p) => $p[$field])->groupBy(fn ($p) => $p[$field])
                ->filter(fn ($g) => $g->count() > 1)
                ->each(function ($group) use ($field, $label) {
                    foreach ($this->pages as $url => $p) {
                        if ($p[$field] === $group->first()[$field]) {
                            $this->pages[$url]['warnings'][] = "$label en double sur ".($group->count() - 1).' autre(s) page(s)';
                        }
                    }
                });
        }
        // Une page anglaise identique à la française = traduction manquante
        foreach ($this->pages as $url => $p) {
            if ($p['lang'] === 'en' && ($fr = $this->pages[preg_replace('/[?&]lang=en$/', '', $url)] ?? null) && $fr['title'] && $fr['title'] === $p['title']) {
                $this->pages[$url]['warnings'][] = 'Titre identique à la version française';
            }
        }
    }

    private function report(array $global): void
    {
        $this->line('<options=bold>Contrôles globaux</>');
        foreach ($global['ok'] as $m) {
            $this->line("  <fg=green>✔</> $m");
        }
        foreach ($global['warnings'] as $m) {
            $this->line("  <fg=yellow>!</> $m");
        }
        foreach ($global['errors'] as $m) {
            $this->line("  <fg=red>✘</> $m");
        }
        $this->newLine();

        $rows = [];
        foreach ($this->pages as $url => $p) {
            $score = max(0, 100 - 15 * count($p['errors']) - 4 * count($p['warnings']));
            $rows[] = [$score, Str::limit(preg_replace('#^https?://[^/]+#', '', $url) ?: '/', 55), $p['status'], implode(', ', array_slice($p['schemas'], 2)) ?: '—', count($p['errors']), count($p['warnings'])];
        }
        usort($rows, fn ($a, $b) => $a[0] <=> $b[0]);
        $this->table(['Score', 'Page', 'HTTP', 'Données structurées', 'Erreurs', 'Alertes'], $rows);

        $issues = collect($this->pages)->flatMap(fn ($p) => array_merge(array_map(fn ($e) => ['err', $e], $p['errors']), array_map(fn ($w) => ['warn', $w], $p['warnings'])))
            ->map(fn ($i) => [$i[0], preg_replace('/\d+/', 'n', $i[1])])->groupBy(fn ($i) => $i[0].'|'.$i[1])->map->count()->sortDesc()->take(15);
        if ($issues->isNotEmpty()) {
            $this->line('<options=bold>Problèmes les plus fréquents</>');
            foreach ($issues as $key => $count) {
                [$kind, $msg] = explode('|', $key, 2);
                $this->line(sprintf('  %s %4d × %s', $kind === 'err' ? '<fg=red>✘</>' : '<fg=yellow>!</>', $count, $msg));
            }
            $this->newLine();
        }

        $avg = count($rows) ? round(array_sum(array_column($rows, 0)) / count($rows)) : 0;
        $globalPenalty = 10 * count($global['errors']) + 2 * count($global['warnings']);
        $final = max(0, $avg - $globalPenalty);
        $color = $final >= 90 ? 'green' : ($final >= 70 ? 'yellow' : 'red');
        $this->line("<options=bold>Score SEO / GEO global : <fg=$color>$final / 100</></> (".count($rows).' pages auditées)');
    }
}
