<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Métadonnées de la page courante : titre, description, URL canonique par
 * langue, hreflang, Open Graph / Twitter, directives robots et données
 * structurées schema.org (JSON-LD). Rempli par les contrôleurs, affiché par
 * le gabarit principal.
 */
class Seo
{
    public ?string $title = null;

    public ?string $description = null;

    public ?string $image = null;

    public string $type = 'website';

    public string $robots = 'index,follow,max-image-preview:large,max-snippet:-1';

    public array $schemas = [];

    public array $faq = [];

    public ?string $published = null;

    public ?string $modified = null;

    public function title(?string $title): static
    {
        $this->title = $title ? Str::limit(trim(strip_tags($title)), 60, '…') : null;

        return $this;
    }

    public function description(?string $text): static
    {
        if ($text) {
            $clean = trim(preg_replace('/\s+/', ' ', strip_tags($text)));
            $this->description = Str::limit($clean, 158, '…');
        }

        return $this;
    }

    /** Les réseaux sociaux n'affichent pas le SVG : on bascule sur le logo PNG */
    public function image(?string $url): static
    {
        $this->image = $url && ! str_contains($url, '/illustration/') && ! str_ends_with($url, '.svg') ? $url : null;

        return $this;
    }

    public function type(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function noindex(): static
    {
        $this->robots = 'noindex,follow';

        return $this;
    }

    public function dates($published, $modified = null): static
    {
        $this->published = $published ? \Illuminate\Support\Carbon::parse($published)->toIso8601String() : null;
        $this->modified = $modified ? \Illuminate\Support\Carbon::parse($modified)->toIso8601String() : $this->published;

        return $this;
    }

    public function schema(?array $data): static
    {
        if ($data) {
            $this->schemas[] = $data;
        }

        return $this;
    }

    /** Questions-réponses visibles sur la page et balisées FAQPage */
    public function faq(array $pairs): static
    {
        $this->faq = array_values(array_filter($pairs, fn ($p) => filled($p[0] ?? null) && filled($p[1] ?? null)));

        return $this;
    }

    /** URL de la page dans une langue donnée, préfixée par /fr ou /en pour les pages publiques */
    public function urlFor(string $locale): string
    {
        return locale_url($locale, collect(request()->query())->only(['page'])->filter()->all());
    }

    public function canonical(): string
    {
        return $this->urlFor(app()->getLocale());
    }

    public static function localizedUrl(string $url, string $locale): string
    {
        $root = rtrim(url('/'), '/');

        return preg_replace('~^'.preg_quote($root, '~').'/(?:fr|en)(?=/|\?|$)~', $root.'/'.$locale, $url, 1);
    }

    /** Pages filtrées (paramètres autres que page/lang) : pas d'indexation, liens suivis */
    public function resolveRobots(): string
    {
        $extra = collect(request()->query())->except(['page', 'lang'])->filter(fn ($v) => filled($v));

        return $extra->isNotEmpty() ? 'noindex,follow' : $this->robots;
    }

    public function fullTitle(): string
    {
        $site = 'Trôofê';

        return $this->title ? $this->title.' | '.$site : $site.' — '.__('La table africaine, en un seul lieu.');
    }

    public function ogImage(): string
    {
        return $this->image ?? asset('assets/img/logo/png/logo-horizontal-1200.png');
    }

    /** Graphe JSON-LD complet de la page */
    public function graph(): array
    {
        $graph = [SchemaOrg::organization(), SchemaOrg::website()];
        foreach ($this->schemas as $s) {
            $graph[] = $s;
        }
        if ($this->faq) {
            $graph[] = SchemaOrg::faq($this->faq);
        }

        return ['@context' => 'https://schema.org', '@graph' => $graph];
    }
}
