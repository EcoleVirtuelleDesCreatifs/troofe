<?php

namespace App\Support;

/**
 * Illustrations vectorielles par défaut (aucune photo nécessaire) :
 * danseurs, instruments, objets d'artisanat, plats, événements.
 * Déterministes : même graine → même image.
 */
class Placeholder
{
    private const W = 800;
    private const H = 500;

    private const PAL = [
        ['bg' => '#5A1E0E', 'a' => '#E2A65A', 'b' => '#F3E9DC', 'c' => '#B04A23', 'd' => '#2F5A3A', 'fig' => '#1E0D06', 'wood' => '#8A5A33'],
        ['bg' => '#1F4E5A', 'a' => '#E7B04F', 'b' => '#F3E6CF', 'c' => '#B04A23', 'd' => '#7A2E12', 'fig' => '#0E2329', 'wood' => '#9A6A3C'],
        ['bg' => '#2F4A3A', 'a' => '#E7C17A', 'b' => '#F1E2C5', 'c' => '#C2502A', 'd' => '#5A1E0E', 'fig' => '#13211A', 'wood' => '#9C6B3D'],
        ['bg' => '#E9D3A8', 'a' => '#B04A23', 'b' => '#FBF3E6', 'c' => '#5A1E0E', 'd' => '#2F5A3A', 'fig' => '#3A170A', 'wood' => '#7A4A26'],
        ['bg' => '#F3E9DC', 'a' => '#B5802A', 'b' => '#FFFFFF', 'c' => '#B04A23', 'd' => '#1F4E5A', 'fig' => '#5A1E0E', 'wood' => '#8A5A33'],
        ['bg' => '#3E2A5C', 'a' => '#E6A04A', 'b' => '#F2E3D0', 'c' => '#B5482A', 'd' => '#7FA35A', 'fig' => '#1A1026', 'wood' => '#9A6A3C'],
    ];

    private int $s;

    private array $p;

    public function __construct(private string $visual, string $seed)
    {
        $h = 2166136261;
        foreach (str_split($visual.$seed) as $ch) {
            $h ^= ord($ch);
            $h = ($h * 16777619) & 0xFFFFFFFF;
        }
        $this->s = $h ?: 1;
        $this->r();
        $this->p = self::PAL[(int) floor($this->r() * count(self::PAL))];
    }

    /** Générateur pseudo-aléatoire xorshift32 */
    private function r(): float
    {
        $s = $this->s;
        $s ^= ($s << 13) & 0xFFFFFFFF;
        $s ^= $s >> 17;
        $s ^= ($s << 5) & 0xFFFFFFFF;
        $this->s = $s & 0xFFFFFFFF;

        return $this->s / 4294967296;
    }

    private static function n(float $v): string
    {
        return rtrim(rtrim(number_format($v, 1, '.', ''), '0'), '.');
    }

    public function svg(): string
    {
        $p = $this->p;
        $inner = match ($this->visual) {
            'danse' => $this->dance(),
            'kora', 'balafon', 'djembe', 'dj', 'jazz', 'conte', 'peinture', 'mc' => $this->artist($this->visual),
            'tissage' => $this->weave(),
            'poterie', 'sculpture', 'vannerie', 'bijoux', 'calebasse', 'maroquinerie' => $this->craft($this->visual),
            'evenement' => $this->event(),
            'article' => $this->weave(true),
            default => $this->dish(),
        };
        $s = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 500" preserveAspectRatio="xMidYMid slice"><rect width="800" height="500" fill="'.$p['bg'].'"/>';
        if ($this->visual !== 'tissage' && $this->visual !== 'article') {
            for ($i = 0; $i < 24; $i++) {
                $s .= '<circle cx="'.self::n($this->r() * 800).'" cy="'.self::n($this->r() * 375).'" r="'.self::n(1.5 + $this->r() * 3).'" fill="'.$p['a'].'" opacity="'.self::n(.15 + $this->r() * .35).'"/>';
            }
        }
        $s .= $inner;
        // Bande tissée de la marque
        $cols = [$p['c'], $p['a'], $p['b'], $p['d']];
        for ($x = 0, $k = 0; $x < 800; $k++) {
            $w = 14 + (int) floor($this->r() * 30);
            $s .= '<rect x="'.$x.'" y="482" width="'.$w.'" height="18" fill="'.$cols[$k % 4].'"/>';
            $x += $w;
        }

        return $s.'</svg>';
    }

    private function dancer(float $x, float $by, float $s, array $p): string
    {
        $n = fn ($v) => self::n($v);
        $lean = ($this->r() - .5) * 44 * $s;
        $hx = $x + $lean; $hy = $by - 215 * $s;
        $shx = $hx - $lean * .15; $shy = $hy + 34 * $s;
        $hipx = $x + ($this->r() - .5) * 16 * $s; $hipy = $by - 102 * $s;
        $arm = function (int $dir, bool $up) use ($shx, $shy, $s, $n) {
            return 'M'.$n($shx).' '.$n($shy + 6 * $s).' Q'.$n($shx + $dir * 52 * $s).' '.$n($shy + ($up ? -6 : 36) * $s).' '.$n($shx + $dir * (70 + $this->r() * 28) * $s).' '.$n($shy + ($up ? -(58 + $this->r() * 44) : (54 + $this->r() * 24)) * $s);
        };
        $up1 = $this->r() > .3; $up2 = $this->r() > .4;
        $legL = 'M'.$n($hipx).' '.$n($hipy).' Q'.$n($hipx - (44 + $this->r() * 18) * $s).' '.$n($hipy + 44 * $s).' '.$n($hipx - (26 + $this->r() * 30) * $s).' '.$n($by);
        $legR = 'M'.$n($hipx).' '.$n($hipy).' Q'.$n($hipx + (52 + $this->r() * 18) * $s).' '.$n($hipy + 28 * $s).' '.$n($hipx + (22 + $this->r() * 36) * $s).' '.$n($by - $this->r() * 34 * $s);
        $g = '<ellipse cx="'.$n($x).'" cy="'.$n($by + 6 * $s).'" rx="'.$n(70 * $s).'" ry="'.$n(10 * $s).'" fill="#000" opacity=".18"/>';
        $g .= '<g fill="none" stroke="'.$p['fig'].'" stroke-linecap="round" stroke-linejoin="round"><path d="'.$legL.'" stroke-width="'.$n(13 * $s).'"/><path d="'.$legR.'" stroke-width="'.$n(13 * $s).'"/>';
        $g .= '<path d="M'.$n($shx).' '.$n($shy).' Q'.$n(($shx + $hipx) / 2 + $lean * .35).' '.$n(($shy + $hipy) / 2).' '.$n($hipx).' '.$n($hipy).'" stroke-width="'.$n(28 * $s).'"/><path d="'.$arm(-1, $up1).'" stroke-width="'.$n(11 * $s).'"/><path d="'.$arm(1, $up2).'" stroke-width="'.$n(11 * $s).'"/></g>';
        for ($i = -6; $i <= 6; $i++) {
            $g .= '<line x1="'.$n($hipx + $i * 6 * $s).'" y1="'.$n($hipy - 8 * $s).'" x2="'.$n($hipx + $i * 10.5 * $s + ($this->r() - .5) * 10 * $s).'" y2="'.$n($hipy + (54 + $this->r() * 10) * $s).'" stroke="'.($i % 2 ? $p['a'] : $p['c']).'" stroke-width="'.$n(4.5 * $s).'" stroke-linecap="round"/>';
        }
        $g .= '<circle cx="'.$n($hx).'" cy="'.$n($hy).'" r="'.$n(20 * $s).'" fill="'.$p['fig'].'"/><path d="M'.$n($hx - 22 * $s).' '.$n($hy - 8 * $s).' Q'.$n($hx).' '.$n($hy - 44 * $s).' '.$n($hx + 22 * $s).' '.$n($hy - 8 * $s).' Z" fill="'.$p['c'].'"/>';

        return $g;
    }

    private function dance(): string
    {
        $p = $this->p;
        $s = '<circle cx="'.self::n(800 * (.35 + $this->r() * .3)).'" cy="210" r="'.self::n(150 + $this->r() * 40).'" fill="'.$p['a'].'" opacity=".9"/>';
        for ($i = 0; $i < 4; $i++) {
            $s .= '<path d="M'.(60 + $i * 40).' '.(400 - $i * 30).' Q400 '.(50 + $i * 30).' '.(740 - $i * 40).' '.(400 - $i * 30).'" fill="none" stroke="'.$p['b'].'" stroke-width="3" opacity=".22"/>';
        }
        $n = 2 + (int) floor($this->r() * 2);
        for ($i = 0; $i < $n; $i++) {
            $x = 800 * (.5 + ($i - ($n - 1) / 2) * .27) + ($this->r() - .5) * 30;
            $s .= $this->dancer($x, 454 - ($i % 2) * 6, .95 + $this->r() * .25 - ($i % 2) * .12, $p);
        }

        return $s;
    }

    private function djembe(float $cx, float $base, float $s): string
    {
        $p = $this->p; $n = fn ($v) => self::n($v); $top = $base - 290 * $s;
        $g = '<path d="M'.$n($cx - 86 * $s).' '.$n($top).' Q'.$n($cx - 92 * $s).' '.$n($top + 92 * $s).' '.$n($cx - 30 * $s).' '.$n($top + 165 * $s).' L'.$n($cx - 48 * $s).' '.$n($base).' L'.$n($cx + 48 * $s).' '.$n($base).' L'.$n($cx + 30 * $s).' '.$n($top + 165 * $s).' Q'.$n($cx + 92 * $s).' '.$n($top + 92 * $s).' '.$n($cx + 86 * $s).' '.$n($top).' Z" fill="'.$p['wood'].'"/>';
        for ($i = 0; $i < 11; $i++) {
            $g .= '<line x1="'.$n($cx - 80 * $s + $i / 10 * 160 * $s).'" y1="'.$n($top + 14 * $s).'" x2="'.$n($cx - 34 * $s + (($i + 1) % 11) / 10 * 68 * $s).'" y2="'.$n($top + 150 * $s).'" stroke="'.$p['b'].'" stroke-width="'.$n(2.2 * $s).'" opacity=".75"/>';
        }

        return $g.'<rect x="'.$n($cx - 88 * $s).'" y="'.$n($top - 4 * $s).'" width="'.$n(176 * $s).'" height="'.$n(16 * $s).'" rx="'.$n(8 * $s).'" fill="'.$p['c'].'"/><ellipse cx="'.$n($cx).'" cy="'.$n($top).'" rx="'.$n(86 * $s).'" ry="'.$n(18 * $s).'" fill="'.$p['b'].'"/>';
    }

    private function artist(string $kind): string
    {
        $p = $this->p; $cx = 400; $base = 460;
        $s = '<circle cx="400" cy="225" r="'.self::n(170 + $this->r() * 20).'" fill="'.$p['a'].'" opacity=".18"/><circle cx="400" cy="225" r="'.self::n(120 + $this->r() * 20).'" fill="'.$p['a'].'" opacity=".22"/>';
        switch ($kind) {
            case 'kora':
                $s .= '<line x1="410" y1="460" x2="380" y2="40" stroke="'.$p['wood'].'" stroke-width="14" stroke-linecap="round"/><circle cx="400" cy="340" r="125" fill="'.$p['c'].'"/><circle cx="400" cy="340" r="98" fill="'.$p['b'].'"/>';
                for ($i = 0; $i < 11; $i++) { $s .= '<line x1="'.(360 + $i * 8).'" y1="310" x2="'.self::n(374 + $i * 2.4).'" y2="60" stroke="'.$p['fig'].'" stroke-width="1.6" opacity=".8"/>'; }
                $s .= '<rect x="342" y="300" width="116" height="14" rx="6" fill="'.$p['wood'].'"/>';
                break;
            case 'balafon':
                $s .= '<rect x="120" y="215" width="560" height="12" rx="6" fill="'.$p['wood'].'"/><rect x="120" y="330" width="560" height="12" rx="6" fill="'.$p['wood'].'"/>';
                for ($i = 0; $i < 11; $i++) { $x = 135 + $i * 49; $h = 150 - $i * 7; $s .= '<circle cx="'.($x + 18).'" cy="'.(352 + (150 - $h) / 2 + 30).'" r="'.self::n(26 - $i * 1.3).'" fill="'.$p['c'].'"/><rect x="'.$x.'" y="'.(200 + (150 - $h) / 2).'" width="36" height="'.$h.'" rx="6" fill="'.($i % 2 ? $p['wood'] : $p['a']).'"/>'; }
                break;
            case 'djembe':
                $s .= $this->djembe(250, $base, .95).$this->djembe(440, $base, 1.15).$this->djembe(615, $base, .8);
                break;
            case 'dj':
                $s .= '<rect x="130" y="250" width="540" height="160" rx="22" fill="'.$p['fig'].'"/>';
                foreach ([275, 525] as $x) { $s .= '<circle cx="'.$x.'" cy="330" r="70" fill="'.$p['c'].'"/>'; for ($k = 0; $k < 5; $k++) { $s .= '<circle cx="'.$x.'" cy="330" r="'.(62 - $k * 10).'" fill="none" stroke="#000" stroke-width="1.4" opacity=".35"/>'; } $s .= '<circle cx="'.$x.'" cy="330" r="16" fill="'.$p['a'].'"/>'; }
                for ($k = 0; $k < 4; $k++) { $s .= '<path d="M'.(200 - $k * 28).' '.(210 - $k * 26).' Q400 '.(100 - $k * 40).' '.(600 + $k * 28).' '.(210 - $k * 26).'" fill="none" stroke="'.$p['a'].'" stroke-width="5" opacity="'.self::n(.8 - $k * .17).'" stroke-linecap="round"/>'; }
                break;
            case 'peinture':
                $s .= '<line x1="300" y1="460" x2="400" y2="70" stroke="'.$p['wood'].'" stroke-width="12" stroke-linecap="round"/><line x1="500" y1="460" x2="400" y2="70" stroke="'.$p['wood'].'" stroke-width="12" stroke-linecap="round"/><rect x="250" y="110" width="300" height="230" rx="6" fill="'.$p['b'].'"/>';
                $cols = [$p['c'], $p['a'], $p['d'], $p['fig']];
                for ($i = 0; $i < 9; $i++) { $x = 270 + $this->r() * 240; $y = 130 + $this->r() * 180; $rr = 18 + $this->r() * 40; $s .= '<ellipse cx="'.self::n($x).'" cy="'.self::n($y).'" rx="'.self::n($rr).'" ry="'.self::n($rr * .7).'" fill="'.$cols[$i % 4].'" opacity=".9"/>'; }
                break;
            case 'conte':
                $s .= '<circle cx="620" cy="110" r="55" fill="'.$p['b'].'"/><path d="M320 460 Q352 300 348 200 L398 200 Q396 300 430 460 Z" fill="'.$p['fig'].'"/><g stroke="'.$p['fig'].'" stroke-width="14" stroke-linecap="round" fill="none"><path d="M370 205 Q330 150 260 140"/><path d="M380 205 Q390 140 360 90"/><path d="M390 205 Q440 150 520 150"/></g>';
                foreach ([[255, 135], [355, 85], [525, 145], [300, 150], [460, 128]] as [$x, $y]) { $s .= '<ellipse cx="'.$x.'" cy="'.$y.'" rx="'.self::n(40 + $this->r() * 16).'" ry="'.self::n(18 + $this->r() * 8).'" fill="'.$p['d'].'"/>'; }
                $s .= '<path d="M560 460 l25 -70 l25 70 z" fill="'.$p['c'].'"/><path d="M572 460 l13 -40 l13 40 z" fill="'.$p['a'].'"/>';
                break;
            case 'mc':
                $s .= '<path d="M400 40 L250 460 L550 460 Z" fill="'.$p['b'].'" opacity=".14"/><line x1="400" y1="460" x2="400" y2="230" stroke="'.$p['fig'].'" stroke-width="10"/><rect x="372" y="130" width="56" height="110" rx="28" fill="'.$p['c'].'"/>';
                $s .= '<path d="M470 120 h190 a24 24 0 0 1 24 24 v60 a24 24 0 0 1 -24 24 h-140 l-30 30 v-30 h-20 a24 24 0 0 1 -24 -24 v-60 a24 24 0 0 1 24 -24z" fill="'.$p['a'].'"/>';
                break;
            default: // orchestre
                $s .= '<path d="M250 460 L250 170 Q250 120 300 120 L330 120" fill="none" stroke="'.$p['a'].'" stroke-width="18" stroke-linecap="round"/><path d="M330 120 L520 260 Q560 300 600 250 L640 200" fill="none" stroke="'.$p['a'].'" stroke-width="22" stroke-linecap="round"/><path d="M600 250 L700 150 L720 290 Z" fill="'.$p['a'].'"/>';
                foreach ([[150, 140], [180, 90], [680, 90]] as [$x, $y]) { $s .= '<g fill="'.$p['b'].'"><ellipse cx="'.$x.'" cy="'.($y + 44).'" rx="14" ry="10"/><rect x="'.($x + 11).'" y="'.$y.'" width="4" height="44"/></g>'; }
        }

        return $s;
    }

    private function weave(bool $soft = false): string
    {
        $p = $this->p; $cols = [$p['c'], $p['a'], $p['d'], $p['fig'], $p['b']]; $s = '';
        for ($x = 0, $k = 0; $x < 800; $k++) {
            $w = 56 + (int) floor($this->r() * 40);
            for ($y = 0; $y < 482;) {
                $h = 40 + (int) floor($this->r() * 90); $c = $cols[($k + intdiv($y, 40)) % 5];
                $s .= '<rect x="'.$x.'" y="'.$y.'" width="'.$w.'" height="'.$h.'" fill="'.$c.'"/>';
                if ($this->r() > .5) {
                    for ($z = 0; $z < 4; $z++) { $yy = $y + 12 + $z * $h / 4; $s .= '<path d="M'.$x.' '.self::n($yy).' l'.self::n($w / 4).' -8 l'.self::n($w / 4).' 8 l'.self::n($w / 4).' -8 l'.self::n($w / 4).' 8" fill="none" stroke="'.$cols[($k + 2) % 5].'" stroke-width="3"/>'; }
                } else {
                    $s .= '<rect x="'.self::n($x + $w * .25).'" y="'.self::n($y + $h * .25).'" width="'.self::n($w * .5).'" height="'.self::n($h * .5).'" fill="'.$cols[($k + 3) % 5].'"/>';
                }
                $y += $h;
            }
            $x += $w;
        }

        return $soft ? $s.'<rect width="800" height="500" fill="'.$p['bg'].'" opacity=".55"/>' : $s;
    }

    private function pot(float $cx, float $base, float $sc, string $col): string
    {
        $p = $this->p; $n = fn ($v) => self::n($v); $ry = 95 * $sc; $rx = 112 * $sc; $cy = $base - $ry;
        $g = '<ellipse cx="'.$n($cx).'" cy="'.$n($cy).'" rx="'.$n($rx).'" ry="'.$n($ry).'" fill="'.$col.'"/><rect x="'.$n($cx - 40 * $sc).'" y="'.$n($cy - $ry - 22 * $sc).'" width="'.$n(80 * $sc).'" height="'.$n(34 * $sc).'" fill="'.$col.'"/><ellipse cx="'.$n($cx).'" cy="'.$n($cy - $ry - 22 * $sc).'" rx="'.$n(52 * $sc).'" ry="'.$n(12 * $sc).'" fill="'.$p['wood'].'"/>';
        for ($i = 0; $i < 9; $i++) { $x = $cx - $rx * .75 + $i * $rx * .19; $g .= '<path d="M'.$n($x).' '.$n($cy - 12 * $sc).' l'.$n(9 * $sc).' '.$n(16 * $sc).' l'.$n(9 * $sc).' '.$n(-16 * $sc).'" fill="none" stroke="'.$p['b'].'" stroke-width="'.$n(3 * $sc).'" opacity=".7"/>'; }

        return $g;
    }

    private function craft(string $kind): string
    {
        $p = $this->p;
        $s = '<circle cx="'.self::n(800 * (.3 + $this->r() * .4)).'" cy="190" r="'.self::n(160 + $this->r() * 30).'" fill="'.$p['a'].'" opacity=".85"/>';
        switch ($kind) {
            case 'poterie':
                $s .= '<rect x="60" y="460" width="680" height="10" fill="'.$p['wood'].'"/>'.$this->pot(200, 460, .85, $p['c']).$this->pot(420, 460, 1.2, $p['wood']).$this->pot(625, 460, .7, $p['c']);
                break;
            case 'sculpture':
                $s .= '<rect x="330" y="420" width="140" height="40" fill="'.$p['wood'].'"/><path d="M400 70 C480 70 500 160 492 230 C484 300 450 370 400 380 C350 370 316 300 308 230 C300 160 320 70 400 70Z" fill="'.$p['fig'].'"/><path d="M345 210 q25 -18 45 0 q-22 14 -45 0z M410 210 q20 -18 45 0 q-23 14 -45 0z" fill="'.$p['b'].'"/><ellipse cx="400" cy="325" rx="26" ry="12" fill="'.$p['c'].'"/>';
                for ($i = 0; $i < 16; $i++) { $s .= '<line x1="'.(352 + $i * 6).'" y1="365" x2="'.self::n(340 + $i * 8 + ($this->r() - .5) * 10).'" y2="420" stroke="'.$p['a'].'" stroke-width="3" stroke-linecap="round"/>'; }
                break;
            case 'vannerie':
                $s .= '<path d="M190 190 Q200 460 400 460 Q600 460 610 190 Z" fill="'.$p['wood'].'"/><ellipse cx="400" cy="190" rx="210" ry="40" fill="'.$p['c'].'"/>';
                foreach ([[240, $p['a']], [300, $p['d']], [360, $p['a']]] as [$y, $c]) { $s .= '<path d="M'.(200 + ($y - 190) * .12).' '.$y.' Q400 '.($y + 40).' '.(600 - ($y - 190) * .12).' '.$y.'" fill="none" stroke="'.$c.'" stroke-width="20"/>'; }
                $s .= '<path d="M260 180 Q400 -40 540 180" fill="none" stroke="'.$p['wood'].'" stroke-width="16" stroke-linecap="round"/>';
                break;
            case 'bijoux':
                for ($i = 0; $i <= 26; $i++) { $t = $i / 26; $s .= '<circle cx="'.self::n(170 + $t * 460).'" cy="'.self::n(110 + sin($t * M_PI) * 220).'" r="'.($i % 3 ? 11 : 15).'" fill="'.($i % 3 ? $p['c'] : '#E2B14A').'"/>'; }
                $s .= '<path d="M370 340 L400 300 L430 340 L400 410 Z" fill="#E2B14A" stroke="#9A6A1C" stroke-width="3"/>';
                break;
            case 'calebasse':
                foreach ([[200, .85], [400, 1.15], [600, .8]] as [$x, $sc]) { $rr = 120 * $sc; $s .= '<path d="M'.self::n($x - $rr).' '.self::n(460 - $rr * .75).' A'.self::n($rr).' '.self::n($rr * .75).' 0 0 0 '.self::n($x + $rr).' '.self::n(460 - $rr * .75).' Z" fill="'.$p['a'].'"/><ellipse cx="'.$x.'" cy="'.self::n(460 - $rr * .75).'" rx="'.self::n($rr).'" ry="'.self::n($rr * .18).'" fill="'.$p['wood'].'"/>'; }
                break;
            default:
                $s .= '<path d="M260 200 Q260 160 300 160 L500 160 Q540 160 540 200 L560 460 L240 460 Z" fill="'.$p['wood'].'"/><path d="M260 200 L540 200 L520 300 Q400 330 280 300 Z" fill="'.$p['c'].'"/><path d="M300 160 Q400 30 500 160" fill="none" stroke="'.$p['fig'].'" stroke-width="16" stroke-linecap="round"/>';
        }

        return $s;
    }

    private function dish(): string
    {
        $p = $this->p; $s = '<circle cx="400" cy="250" r="200" fill="#000" opacity=".12"/><circle cx="400" cy="240" r="195" fill="'.$p['b'].'"/><circle cx="400" cy="240" r="150" fill="'.$p['c'].'"/>';
        for ($i = 0; $i < 7; $i++) { $a = $this->r() * M_PI * 2; $d = $this->r() * 90; $s .= '<circle cx="'.self::n(400 + cos($a) * $d).'" cy="'.self::n(240 + sin($a) * $d).'" r="'.self::n(18 + $this->r() * 26).'" fill="'.[$p['a'], $p['d'], $p['wood'], $p['b']][$i % 4].'"/>'; }

        return $s.'<path d="M160 120 q40 -40 80 0" fill="none" stroke="'.$p['b'].'" stroke-width="6" opacity=".4"/>';
    }

    private function event(): string
    {
        $p = $this->p; $s = '<path d="M0 60 Q400 200 800 60" fill="none" stroke="'.$p['b'].'" stroke-width="2" opacity=".6"/>';
        for ($i = 0; $i < 14; $i++) { $t = $i / 13; $x = $t * 800; $y = 60 + sin($t * M_PI) * 70; $s .= '<circle cx="'.self::n($x).'" cy="'.self::n($y + 14).'" r="12" fill="'.[$p['a'], $p['c'], $p['b']][$i % 3].'"/>'; }

        return $s.$this->dancer(300, 454, .9, $p).$this->dancer(520, 450, .8, $p);
    }
}
