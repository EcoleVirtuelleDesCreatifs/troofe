<?php

namespace App\Support;

use Illuminate\Support\Str;

class PhotoLibrary
{
    public const PEXELS = [
        'home/table-africaine.jpg' => ['healthy-meal-on-wooden-tray', 13994299, 'Keesha’s Kitchen'],
        'home/restaurant.jpg' => ['cozy-restaurant-interior-with-set-table', 31837790, 'Ani Hadushaj'],
        'home/jollof.jpg' => ['fried-cubes-and-rice-on-white-plate', 17952748, 'Studio Saiz'],
        'home/cuisine.jpg' => ['african-chefs-cooking-traditional-meal-in-rustic-kitchen', 35305037, 'Pexels'],
        'home/table-restaurant.jpg' => ['elegant-restaurant-table-setting-with-warm-lighting', 32738698, 'Richard L'],
        'home/terrasse.jpg' => ['interior-of-terrace-of-cafe', 4946768, 'Maria Orlova'],
        'home/restaurant-jardin.jpg' => ['terrace-of-cafe-with-tables', 6130070, 'Quang Nguyen Vinh'],
        'home/restaurant-ouvert.jpg' => ['rustic-open-air-thatched-roof-restaurant-interior', 30413766, 'Magda Ehlers'],
        'home/cafe.jpg' => ['top-view-of-a-cup-filled-with-coffee-beans', 16480801, 'merwak. raw'],
        'home/patisserie.jpg' => ['a-baker-kneading-a-dough', 7447297, 'Gustavo Fring'],
        'photos/danse.jpg' => ['traditional-african-cultural-dance-performance', 34060929, 'Anchau'],
        'photos/danse-costumes.jpg' => ['traditional-african-dance-ceremony-in-colorful-costume', 31483492, 'Safari Consoler'],
        'photos/djembe.jpg' => ['street-performer-playing-djembe-drum-in-monrovia', 31948227, 'B. Aristotlè Guweh Jr'],
        'photos/musiciens.jpg' => ['traditional-african-musicians-performing-indoors', 34435734, 'Pexels'],
        'photos/tissage.jpg' => ['traditional-loom-weaving-by-skilled-artisan', 28382914, 'Susheel Parihar'],
        'photos/poterie.jpg' => ['hands-shaping-clay-on-pottery-wheel-in-studio', 36717713, 'Letícia Alvares'],
        'photos/sculpture.jpg' => ['professional-craftsman-with-wood-mallet-carving-piece-of-wood', 5711879, 'Anna Shvets'],
        'photos/dj.jpg' => ['close-up-of-dj-mixing-music-on-a-console', 10881631, 'HamZa NOUASRIA'],
        'photos/jazz.jpg' => ['band-performing-on-the-stage', 9001998, 'Yan Krukau'],
        'photos/vannerie.jpg' => ['senior-artisan-weaving-colorful-basket-by-hand', 36720360, 'Andi saiful Sidik'],
        'photos/bijoux.jpg' => ['close-up-of-artisan-crafting-jewelry-with-precision', 33035386, 'Elizabeth Ferreira'],
        'photos/cuir.jpg' => ['carpentry-in-workshop', 5894266, 'Anna Shvets'],
        'photos/peinture.jpg' => ['close-up-of-artist-painting-on-canvas', 33200138, 'Alexander Mass'],
    ];

    public const COMMONS = [
        'home/alloco.jpg' => ['Alloco_poisson_oeuf.jpg', 'Jacquitoz', '4.0'],
        'home/attieke.jpg' => ['Attieke_poisson_in_Abidjan_Côte_d\'Ivoire.JPG', 'Hanay', '4.0'],
        'home/kedjenou.jpg' => ['Kedjenou_poulet_sauce_claire.JPG', 'African Hope', '4.0'],
        'photos/egusi.jpg' => ['Egusi_soup_with_ponmo_and_beef.jpg', 'Bukky658', '4.0'],
        'photos/foutou.jpg' => ['Foutou-sauce_graine.jpg', 'Kanjulien', '4.0'],
        'photos/mafe.jpg' => ['Mafe.malien.jpg', 'Marshiayers', 'CC0'],
        'photos/ndole.jpg' => ['Ndole_royal.jpg', 'Étienne Talla', '4.0'],
        'photos/yassa.jpg' => ['Poulet_yassa_01.jpg', 'Arnaud 25', 'CC0'],
        'photos/thieboudienne.jpg' => ['Thiebou_Djeun.jpg', 'VinceS', '4.0'],
        'photos/waakye.jpg' => ['Ghanaian_Wache_(Waakye)_cuisine_food.jpg', 'Hiroo Yamagata', '2.0'],
        'photos/kora.jpg' => ['Jacques_Burtin,_Kora_(Photo_Vincent_Burtin).jpg', 'Vincent Burtin', '3.0'],
        'photos/balafon.jpg' => ['2_Balafon_players.jpg', 'PGskot', '4.0'],
        'photos/calebasse.jpg' => ['Calebasse_décoratif_de_fabrication_artisanale_05.jpg', 'Akouete', '4.0'],
    ];

    public const DISHES = [
        'alloco' => 'home/alloco.jpg',
        'attieke-poisson-braise' => 'home/attieke.jpg',
        'egusi-soup' => 'photos/egusi.jpg',
        'foutou-sauce-graine' => 'photos/foutou.jpg',
        'jollof-rice' => 'home/jollof.jpg',
        'kedjenou' => 'home/kedjenou.jpg',
        'mafe' => 'photos/mafe.jpg',
        'ndole' => 'photos/ndole.jpg',
        'poulet-yassa' => 'photos/yassa.jpg',
        'thieboudienne' => 'photos/thieboudienne.jpg',
        'waakye' => 'photos/waakye.jpg',
    ];

    public static function srcset(string $url): ?string
    {
        return str_starts_with($url, asset('assets/img/photos/galerie-')) && preg_match('~/galerie-[a-z0-9_]+-1280\.webp$~', $url)
            ? str_replace('-1280.webp', '-640.webp', $url).' 640w, '.$url.' 1280w'
            : null;
    }

    public static function url(string $visual, string $seed): string
    {
        if ($visual === 'plat' && isset(self::DISHES[$seed])) {
            return asset('assets/img/'.self::DISHES[$seed]);
        }

        $subject = Str::ascii(Str::lower($seed));
        $images = match ($visual) {
            'restaurant' => ['home/restaurant.jpg', 'home/restaurant-jardin.jpg', 'home/restaurant-ouvert.jpg', 'home/table-restaurant.jpg', 'home/terrasse.jpg'],
            'danse' => ['photos/galerie-8k2a0148-1280.webp', 'photos/galerie-8k2a8853-1280.webp'],
            'kora' => ['photos/kora.jpg'],
            'balafon' => ['photos/balafon.jpg'],
            'conte' => ['photos/musiciens.jpg'],
            'djembe' => ['photos/djembe.jpg'],
            'dj' => ['photos/galerie-8k2a0378-1280.webp'],
            'jazz' => ['photos/jazz.jpg'],
            'mc' => ['photos/galerie-img_0192-1280.webp'],
            'peinture' => ['photos/peinture.jpg'],
            'tissage' => ['photos/tissage.jpg'],
            'poterie' => ['photos/poterie.jpg'],
            'calebasse' => ['photos/calebasse.jpg'],
            'sculpture' => ['photos/sculpture.jpg'],
            'vannerie' => ['photos/vannerie.jpg'],
            'bijoux' => ['photos/bijoux.jpg'],
            'maroquinerie' => ['photos/cuir.jpg'],
            'evenement' => str_contains($subject, 'atelier') ? ['photos/galerie-8k2a0270-1280.webp', 'photos/galerie-8k2a8903-1280.webp'] : ['photos/galerie-8k2a9994-1280.webp', 'photos/galerie-8k2a8889-1280.webp', 'photos/galerie-8k2a8873-1280.webp'],
            'article' => match (true) {
                str_contains($subject, 'jollof') => ['home/jollof.jpg'],
                str_contains($subject, 'cafe') => ['home/cafe.jpg'],
                str_contains($subject, 'patisserie') => ['home/patisserie.jpg'],
                str_contains($subject, 'chef'), str_contains($subject, 'rose') => ['home/cuisine.jpg'],
                default => ['photos/galerie-8k2a0318-1280.webp', 'photos/galerie-8k2a8916-1280.webp', 'photos/galerie-8k2a9994-1280.webp'],
            },
            default => ['home/table-africaine.jpg', 'home/jollof.jpg'],
        };

        return asset('assets/img/'.$images[hexdec(substr(md5($seed), 0, 6)) % count($images)]);
    }
}
