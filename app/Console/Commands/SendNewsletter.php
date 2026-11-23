<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\Dish;
use App\Models\Event;
use App\Models\NewsletterSubscriber;
use App\Models\Restaurant;
use App\Models\Talent;
use App\Notifications\Newsletter\Digest;
use Illuminate\Console\Command;

/** Lettre hebdomadaire (et mensuelle), dans la langue de chaque abonné */
class SendNewsletter extends Command
{
    protected $signature = 'troofe:newsletter {--frequency=weekly} {--dry-run}';

    protected $description = 'Envoie la lettre d\'information aux abonnés confirmés';

    public function handle(): int
    {
        $freq = $this->option('frequency');
        $since = $freq === 'monthly' ? now()->subMonth() : now()->subWeek();
        $count = 0;

        NewsletterSubscriber::active()->where('frequency', $freq)
            ->where(fn ($q) => $q->whereNull('last_sent_at')->orWhere('last_sent_at', '<', now()->subDays($freq === 'monthly' ? 25 : 6)))
            ->chunkById(200, function ($subs) use ($since, &$count) {
                foreach ($subs as $sub) {
                    app()->setLocale($sub->preferredLocale());
                    $sections = $this->sections($since);
                    if (! collect($sections)->flatten(1)->count()) {
                        continue;
                    }
                    if (! $this->option('dry-run')) {
                        $sub->notify(new Digest($sections));
                        $sub->update(['last_sent_at' => now()]);
                    }
                    $count++;
                }
            });
        $this->info("$count lettre(s) envoyée(s)");

        return self::SUCCESS;
    }

    private function sections($since): array
    {
        return [
            'restaurants' => Restaurant::published()->where('created_at', '>=', $since)->take(4)->get()
                ->map(fn ($r) => ['title' => $r->name, 'line' => $r->cuisine.', '.$r->city, 'url' => route('restaurants.show', $r)])->all(),
            'evenements' => Event::published()->upcoming()->orderBy('starts_at')->take(4)->get()
                ->map(fn ($e) => ['title' => $e->tr('title'), 'line' => date_human($e->starts_at, 'D MMM').', '.$e->city, 'url' => route('events.show', $e)])->all(),
            'culture' => Talent::published()->where(fn ($q) => $q->where('created_at', '>=', $since)->orWhere('is_featured', true))->take(4)->get()
                ->map(fn ($t) => ['title' => $t->name, 'line' => __($t->meta('label')).', '.$t->city, 'url' => $t->url()])->all(),
            'recettes' => Dish::published()->inRandomOrder()->take(2)->get()
                ->map(fn ($d) => ['title' => $d->name, 'line' => $d->origin_country, 'url' => route('dishes.show', $d)])
                ->merge(Article::published()->where('published_at', '>=', $since)->take(3)->get()
                    ->map(fn ($a) => ['title' => $a->tr('title'), 'line' => $a->category, 'url' => route('articles.show', $a)]))->all(),
        ];
    }
}
