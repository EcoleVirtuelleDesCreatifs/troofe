<?php

namespace Database\Seeders;

use App\Models\AdCampaign;
use App\Models\AdSlot;
use App\Models\Article;
use App\Models\Booking;
use App\Models\Dish;
use App\Models\Event;
use App\Models\MenuItem;
use App\Models\NewsletterSubscriber;
use App\Models\Plan;
use App\Models\Restaurant;
use App\Models\Review;
use App\Models\Subscription;
use App\Models\Talent;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Données de démonstration issues de la maquette (database/seeders/data/demo.json).
 * Les dates sont décalées pour rester dans le futur au moment de l'installation.
 * Mot de passe de tous les comptes : demo1234
 */
class DatabaseSeeder extends Seeder
{
    private array $d;

    private array $ids = [];

    private int $shift = 0;

    public function run(): void
    {
        if (app()->isProduction()) {
            throw new \LogicException('Demo data cannot be seeded in production.');
        }
        $this->call(PlanSeeder::class);
        $this->d = json_decode(file_get_contents(__DIR__.'/data/demo.json'), true);
        $this->shift = (int) now()->diffInSeconds(Carbon::parse($this->d['extractedAt']), true);

        $this->users();
        $this->dishes();
        $this->restaurants();
        $this->articles();
        $this->events();
        $this->talents();
        $this->reviews();
        $this->ads();
        $this->demoActivity();
        $this->command?->info('Comptes de démonstration (mot de passe : demo1234) : admin@troofe.ci, et les autres e-mails listés dans la table users.');
    }

    private function at(?string $iso): ?Carbon
    {
        return $iso ? Carbon::parse($iso)->addSeconds($this->shift) : null;
    }

    private function status(string $s): string
    {
        return ['publie' => 'published', 'en_attente' => 'pending', 'suspendu' => 'suspended', 'refuse' => 'rejected', 'annule' => 'suspended', 'brouillon' => 'draft', 'programme' => 'published', 'archive' => 'draft'][$s] ?? 'draft';
    }

    private function country(string $city): string
    {
        return ['Abidjan' => 'Côte d\'Ivoire', 'Bouaké' => 'Côte d\'Ivoire', 'Yamoussoukro' => 'Côte d\'Ivoire', 'Accra' => 'Ghana', 'Kumasi' => 'Ghana', 'Lagos' => 'Nigeria', 'Dakar' => 'Sénégal', 'Cotonou' => 'Bénin', 'Douala' => 'Cameroun', 'Lomé' => 'Togo', 'Bamako' => 'Mali'][$city] ?? 'Côte d\'Ivoire';
    }

    private function users(): void
    {
        foreach ($this->d['users'] as $u) {
            $roles = collect($u['roles'])->map(fn ($r) => ['controleur' => 'organisateur', 'sponsor' => 'annonceur'][$r] ?? $r)
                ->filter(fn ($r) => array_key_exists($r, User::ROLES))->unique()->values()->all();
            $owns = collect([...$this->d['troupes'], ...$this->d['artistes'], ...$this->d['artisans']])->contains(fn ($t) => ($t['ownerId'] ?? null) === $u['id']);
            if ($owns) {
                $roles[] = 'artiste';
            }
            $user = User::updateOrCreate(['email' => $u['email']], [
                'name' => $u['nom'], 'password' => 'demo1234', 'phone' => $u['tel'] ?? null, 'city' => $u['ville'] ?? null,
                'roles' => array_values(array_unique($roles ?: ['membre'])), 'locale' => 'fr', 'is_active' => ($u['statut'] ?? 'actif') === 'actif',
                'notification_settings' => array_fill_keys(array_keys(config('troofe.notification_categories')), true),
            ]);
            $user->forceFill(['email_verified_at' => now(), 'created_at' => $this->at($u['created'] ?? null) ?? now()])->save();
            $this->ids['u'][$u['id']] = $user->id;
        }
        // Un compte anglophone pour tester les e-mails en anglais
        $en = User::updateOrCreate(['email' => 'guest.en@troofe.ci'], ['name' => 'Grace Mensah', 'password' => 'demo1234', 'city' => 'Accra', 'roles' => ['membre'], 'locale' => 'en',
            'notification_settings' => array_fill_keys(array_keys(config('troofe.notification_categories')), true)]);
        $en->forceFill(['email_verified_at' => now()])->save();
    }

    private function uid(?string $key): ?int
    {
        return $key ? ($this->ids['u'][$key] ?? null) : null;
    }

    private function dishes(): void
    {
        foreach ($this->d['plats'] as $p) {
            $dish = Dish::updateOrCreate(['name' => $p['nom']], [
                'origin_country' => $p['origines'][0] ?? 'Afrique de l\'Ouest',
                'region' => count($p['origines'] ?? []) > 1 ? implode(', ', $p['origines']) : null,
                'category' => $p['famille'] ?? 'Plat',
                'summary' => Str::limit($p['originNote'] ?? '', 250),
                'history' => '<p>'.e($p['originNote'] ?? '').'</p>'.(! empty($p['variantes']) ? '<p><strong>Variantes :</strong> '.e($p['variantes']).'</p>' : '').(! empty($p['presentation']) ? '<p>'.e($p['presentation']).'</p>' : ''),
                'ingredients' => $p['ingredients'] ?? [],
                'recipe' => ! empty($p['preparation']) ? '<p>'.e($p['preparation']).'</p>' : null,
                'status' => $this->status($p['statut'] ?? 'publie'),
            ]);
            $this->ids['p'][$p['id']] = $dish->id;
        }
        // Exemple de traduction anglaise
        Dish::where('name', 'Attiéké poisson braisé')->update([
            'summary_en' => 'Fermented cassava couscous from the Ivorian lagoon region, served with grilled fish. Protected by a geographical indication since 2024.',
            'history_en' => '<p>Attiéké comes from the southern lagoon peoples of Côte d\'Ivoire (Adjoukrou, Ébrié, Avikam). It is served warm and slightly tangy, with a whole grilled fish and a tomato, onion and chilli salad.</p>',
            'recipe_en' => '<p>Cassava is peeled, grated, fermented, pressed, granulated, dried and steamed. The fish is marinated with garlic, ginger and chilli before grilling.</p>',
        ]);
    }

    private function restaurants(): void
    {
        $days = ['lun' => 'Lundi', 'mar' => 'Mardi', 'mer' => 'Mercredi', 'jeu' => 'Jeudi', 'ven' => 'Vendredi', 'sam' => 'Samedi', 'dim' => 'Dimanche'];
        foreach ($this->d['restaurants'] as $r) {
            $services = $r['services'] ?? [];
            $resto = Restaurant::updateOrCreate(['name' => $r['nom']], [
                'owner_id' => $this->uid($r['ownerId'] ?? null), 'cuisine' => $r['cuisine'], 'city' => $r['ville'], 'country' => $this->country($r['ville']),
                'district' => $r['quartier'] ?? null, 'address' => $r['adresse'] ?? null, 'phone' => $r['tel'] ?? null, 'whatsapp' => $r['whatsapp'] ?: null,
                'description' => $r['description'] ?? null, 'tagline' => Str::limit(strtok($r['description'] ?? '', '.'), 120, ''),
                'price_level' => max(1, (int) ($r['budget'] ?? 2)),
                'opening_hours' => collect($r['horaires'] ?? [])->mapWithKeys(fn ($v, $k) => [$days[$k] ?? $k => $v])->all(),
                'accepts_orders' => (bool) array_intersect($services, ['retrait', 'livraison_resto', 'livraison']),
                'delivery' => (bool) array_intersect($services, ['livraison_resto', 'livraison']), 'delivery_fee' => 1000,
                'status' => $this->status($r['statut']), 'is_verified' => ! empty($r['verifie']), 'is_featured' => ! empty($r['sponsorise']),
            ]);
            $resto->forceFill(['views' => $r['vues'] ?? 0, 'created_at' => $this->at($r['created'] ?? null) ?? now()])->save();
            $this->ids['r'][$r['id']] = $resto->id;
        }
        Restaurant::where('name', 'Maquis Chez Tantie Rose')->update(['tagline_en' => 'The neighbourhood maquis famous for grilled fish in Angré', 'description_en' => 'The neighbourhood maquis that made grilled fish famous in Angré. Fresh attiéké every day, golden alloco to order and kedjenou slow-cooked in a clay pot at weekends.']);

        $pos = 0;
        foreach ($this->d['menus'] as $m) {
            if (! isset($this->ids['r'][$m['restoId']])) {
                continue;
            }
            MenuItem::updateOrCreate(['restaurant_id' => $this->ids['r'][$m['restoId']], 'name' => $m['nom']], [
                'category' => $m['categorie'] ?? 'Plats', 'price' => (int) $m['prix'], 'description' => $m['description'] ?? null,
                'is_available' => $m['dispo'] ?? true, 'position' => $pos++,
            ]);
            if (! empty($m['platId']) && isset($this->ids['p'][$m['platId']])) {
                Dish::find($this->ids['p'][$m['platId']])->restaurants()->syncWithoutDetaching([$this->ids['r'][$m['restoId']]]);
            }
        }
    }

    private function articles(): void
    {
        foreach ($this->d['articles'] as $a) {
            $body = collect(preg_split("/\n{2,}/", trim($a['contenu'] ?? '')))->filter()->map(fn ($p) => '<p>'.e(trim($p)).'</p>')->implode("\n");
            Article::updateOrCreate(['title' => $a['titre']], [
                'author_id' => $this->ids['u']['u1'] ?? null, 'category' => $a['categorie'] ?? 'Actualité', 'excerpt' => $a['chapo'] ?? null,
                'body' => $body ?: '<p>'.e($a['chapo'] ?? '').'</p>', 'status' => in_array($a['statut'], ['publie', 'programme'], true) ? 'published' : 'draft',
                'published_at' => $a['statut'] === 'programme' ? $this->at($a['date'] ?? null) : (($d = $this->at($a['date'] ?? null)) && $d->isPast() ? $d : now()->subDay()),
            ]);
        }
    }

    private function events(): void
    {
        foreach ($this->d['events'] as $e) {
            $event = Event::updateOrCreate(['title' => $e['titre']], [
                'organizer_id' => $this->uid($e['orgId'] ?? null), 'category' => $e['theme'] ?? 'Dégustation',
                'excerpt' => Str::limit($e['description'] ?? '', 380), 'description' => '<p>'.e($e['description'] ?? '').'</p>'.(! empty($e['programme']) ? '<p><strong>Programme :</strong> '.e($e['programme']).'</p>' : ''),
                'venue' => $e['lieu'], 'city' => $e['ville'], 'country' => $this->country($e['ville']),
                'starts_at' => $this->at($e['date']), 'ends_at' => $this->at($e['fin'] ?? null),
                'status' => $this->status($e['statut']), 'is_featured' => $e['id'] === 'e1',
            ]);
            $this->ids['e'][$e['id']] = $event->id;
            if (! empty($e['billetterie'])) {
                foreach ($e['tarifs'] ?? [] as $t) {
                    $event->ticketTypes()->updateOrCreate(['name' => $t['nom']], ['price' => (int) $t['prix'], 'quantity' => (int) ($t['quota'] ?? 100), 'sold' => (int) ($t['vendus'] ?? 0)]);
                }
            }
        }
        Event::where('title', 'Nuit des saveurs d\'Abidjan')->update(['title_en' => 'Abidjan Night of Flavours', 'excerpt_en' => 'Twenty partner restaurants, a tasting trail from 6 pm to midnight and an open-air concert. One pass gives access to six plates.']);
    }

    private function talents(): void
    {
        foreach (['troupe' => 'troupes', 'artiste' => 'artistes', 'artisan' => 'artisans'] as $type => $key) {
            foreach ($this->d[$key] as $t) {
                $craft = $type === 'artisan';
                $talent = Talent::updateOrCreate(['name' => $t['nom']], [
                    'type' => $type, 'owner_id' => $this->uid($t['ownerId'] ?? null), 'tagline' => $t['accroche'], 'category' => $t['categorie'],
                    'visual' => $type === 'troupe' ? 'danse' : ($t['kind'] ?? null),
                    'tags' => $t['styles'] ?? $t['disciplines'] ?? $t['techniques'] ?? [], 'materials' => $t['materiaux'] ?? null,
                    'repertoire' => $t['repertoire'] ?? null, 'languages' => $t['langues'] ?? null, 'audiences' => $t['publics'] ?? null, 'zones' => $t['zone'] ?? null,
                    'maker_name' => $t['artisan'] ?? null, 'members' => $t['membres'] ?? $t['effectif'] ?? null, 'musicians' => $t['musiciens'] ?? null,
                    'since_year' => $t['annee'] ?? null, 'min_notice_days' => $t['delai'] ?? 7,
                    'city' => $t['ville'], 'country' => $t['pays'], 'district' => $t['quartier'] ?? null, 'description' => $t['description'],
                    'pro_offer' => $t['pros'] ?? null, 'visit_price' => $t['visite']['prix'] ?? null, 'visit_duration' => $t['visite']['duree'] ?? null, 'visit_details' => $t['visite']['details'] ?? null,
                    'phone' => $t['tel'] ?? null, 'whatsapp' => $t['whatsapp'] ?: null,
                    'status' => $this->status($t['statut']), 'is_verified' => (bool) $t['verifie'], 'is_featured' => (bool) $t['sponsorise'],
                ]);
                $talent->forceFill(['views' => $t['vues'] ?? 0])->save();
                $this->ids['t'][$t['id']] = $talent->id;

                $talent->offers()->delete();
                foreach ($craft ? ($t['produits'] ?? []) : ($t['formules'] ?? []) as $i => $o) {
                    $talent->offers()->create(['name' => $o['nom'], 'price' => (int) $o['prix'], 'duration' => $craft ? null : ($o['duree'] ?? null), 'lead_time' => $craft ? ($o['delai'] ?? null) : null,
                        'details' => $craft ? ($o['desc'] ?? null) : ($o['details'] ?? null), 'is_custom' => (bool) ($o['surMesure'] ?? false), 'position' => $i]);
                }
                $talent->unavailabilities()->delete();
                foreach ($t['indispo'] ?? [] as $date) {
                    $talent->unavailabilities()->create(['date' => Carbon::parse($date)->addSeconds($this->shift)->toDateString()]);
                }
                $talent->events()->sync(collect($t['events'] ?? [])->map(fn ($e) => $this->ids['e'][$e] ?? null)->filter()->all());
                $talent->restaurants()->sync(collect($t['restos'] ?? [])->map(fn ($r) => $this->ids['r'][$r] ?? null)->filter()->all());
                $talent->dishes()->sync(collect($t['plats'] ?? [])->map(fn ($p) => $this->ids['p'][$p] ?? null)->filter()->all());
            }
        }
        Talent::where('name', 'Troupe Zaouli Yohou')->update(['tagline_en' => 'The zaouli mask, its dizzying footwork and Gouro drums.', 'description_en' => 'Founded by dancers from the Bouaflé region, the troupe performs the zaouli, a Gouro mask dance listed as UNESCO intangible heritage. Each show combines mask dance, songs and drums, with a short introduction for the audience.']);
        Talent::where('name', 'DJ Lagune')->update(['tagline_en' => 'Afrobeats, coupé-décalé and classics: the dance floor never empties.']);
        Talent::where('name', 'Poterie de Katiola')->update(['tagline_en' => 'The clay pots of kedjenou, shaped by hand without a wheel.']);
    }

    private function reviews(): void
    {
        $map = ['restaurant' => ['r', Restaurant::class], 'troupe' => ['t', Talent::class], 'artiste' => ['t', Talent::class], 'artisan' => ['t', Talent::class]];
        foreach ($this->d['reviews'] as $v) {
            [$k, $class] = $map[$v['cible']] ?? [null, null];
            $target = $k ? ($this->ids[$k][$v['cibleId']] ?? null) : null;
            $user = $this->uid($v['userId']);
            if (! $target || ! $user) {
                continue;
            }
            Review::updateOrCreate(['user_id' => $user, 'reviewable_type' => (new $class)->getMorphClass(), 'reviewable_id' => $target], [
                'rating' => $v['note'], 'body' => $v['texte'], 'status' => $v['statut'] === 'publie' ? 'published' : 'hidden',
                'created_at' => $this->at($v['date']) ?? now(),
            ]);
        }
    }

    private function ads(): void
    {
        $fr = ['slide' => 'leaderboard'];
        foreach ($this->d['adSlots'] as $s) {
            $slot = AdSlot::updateOrCreate(['code' => $s['id']], ['name' => $s['nom'], 'page' => $s['page'], 'format' => $fr[$s['format']] ?? $s['format'], 'dimensions' => $s['dims'] ?? null, 'price_per_day' => $s['prixJour'], 'is_active' => $s['actif']]);
            $this->ids['slot'][$s['id']] = $slot->id;
        }
        foreach ($this->d['campagnes'] as $c) {
            if (! isset($this->ids['slot'][$c['slotId']])) {
                continue;
            }
            $stats = collect($c['stats'] ?? []);
            $url = $c['creative']['lien'] ?? '/';
            $campaign = AdCampaign::updateOrCreate(['title' => $c['creative']['titre'], 'ad_slot_id' => $this->ids['slot'][$c['slotId']]], [
                'user_id' => $this->uid($c['userId'] ?? null), 'advertiser' => $c['annonceur'], 'text' => $c['creative']['texte'] ?? null, 'cta' => $c['creative']['cta'] ?? 'Découvrir',
                'url' => str_starts_with($url, 'http') ? $url : url('/'.ltrim(str_replace('#', '', $url), '/')), 'color' => $c['creative']['couleur'] ?? 'brun',
                'starts_on' => $this->at($c['debut'])->toDateString(), 'ends_on' => $this->at($c['fin'])->toDateString(), 'amount' => (int) $c['montant'],
                'status' => ['programmee' => 'active', 'active' => 'active', 'terminee' => 'terminee', 'en_attente' => 'en_attente'][$c['statut']] ?? 'en_attente',
            ]);
            $campaign->forceFill(['impressions' => $stats->sum('i'), 'clicks' => $stats->sum('c')])->save();
        }
    }

    /** Abonnements, réservations et inscriptions à la lettre pour tester les notifications */
    private function demoActivity(): void
    {
        $pro = Plan::firstWhere('slug', 'pro');
        $resto = User::firstWhere('email', 'chef@troofe.ci');
        $event = User::firstWhere('email', 'event@troofe.ci');
        if ($resto && $pro) {
            Subscription::updateOrCreate(['user_id' => $resto->id, 'plan_id' => $pro->id], ['billing_cycle' => 'monthly', 'status' => 'active', 'starts_at' => now()->subDays(24), 'ends_at' => now()->addDays(6), 'auto_renew' => true, 'reminders_sent' => []]);
        }
        if ($event && ($ess = Plan::firstWhere('slug', 'essentiel'))) {
            Subscription::updateOrCreate(['user_id' => $event->id, 'plan_id' => $ess->id], ['billing_cycle' => 'yearly', 'status' => 'active', 'starts_at' => now()->subMonths(2), 'ends_at' => now()->addMonths(10), 'auto_renew' => true]);
        }
        $client = User::firstWhere('email', 'aya@troofe.ci') ?? User::whereJsonContains('roles', 'membre')->whereJsonDoesntContain('roles', 'admin')->first();
        $t5 = isset($this->ids['t']['t5']) ? Talent::find($this->ids['t']['t5']) : null;
        if ($client && $t5 && ! Booking::where('talent_id', $t5->id)->exists()) {
            Booking::create(['talent_id' => $t5->id, 'user_id' => $client->id, 'talent_offer_id' => $t5->offers()->first()?->id, 'kind' => 'prestation', 'event_date' => now()->addDays(20)->toDateString(),
                'city' => 'Accra', 'venue' => 'Labadi Beach Hotel', 'occasion' => 'Entreprises', 'guests' => 120, 'contact_phone' => '+233 24 000 0000', 'message' => 'Accueil des partenaires d\'un séminaire.', 'status' => 'nouvelle']);
        }
        foreach (User::whereNotNull('email_verified_at')->take(6)->get() as $u) {
            NewsletterSubscriber::updateOrCreate(['email' => $u->email], ['name' => $u->name, 'user_id' => $u->id, 'locale' => $u->locale, 'confirmed_at' => now(), 'topics' => array_keys(config('troofe.newsletter_topics')), 'source' => 'demo']);
        }
    }
}
