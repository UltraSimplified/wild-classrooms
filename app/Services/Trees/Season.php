<?php

declare(strict_types=1);

namespace App\Services\Trees;

/**
 * The time of year a woodland tree is drawn in.
 *
 * The leaves are achievements rather than foliage — one for each month and
 * category a member has found something in — so the tree never goes bare and
 * a leaf once earned is never taken away. What changes with the season is how
 * those leaves look: fuller in summer, turned in autumn, under snow in winter.
 */
enum Season: string
{
    case Spring = 'spring';
    case Summer = 'summer';
    case Autumn = 'autumn';
    case Winter = 'winter';

    public static function forMonth(int $month): self
    {
        return match (true) {
            $month >= 3 && $month <= 5 => self::Spring,
            $month >= 6 && $month <= 8 => self::Summer,
            $month >= 9 && $month <= 11 => self::Autumn,
            // December, January and February, and anything out of range.
            default => self::Winter,
        };
    }

    /**
     * Leaf colours to swap, keyed by the value the artwork already carries:
     * the front face, the paler underside, and the outline around both.
     *
     * The artwork is drawn in its spring and summer colours, so those two
     * seasons change nothing.
     *
     * @return array<string, string>
     */
    public function recolour(): array
    {
        return match ($this) {
            self::Spring, self::Summer => [],
            self::Autumn => [
                '#6e9d03' => '#c4651c',
                '#8cab46' => '#d9a62e',
                // A deep green outline around an orange leaf reads as a
                // mistake, so the outline warms up with the fill.
                '#0a3309' => '#5a3410',
            ],
            self::Winter => [
                '#6e9d03' => '#6f8a52',
                '#8cab46' => '#93a67c',
            ],
        };
    }

    /**
     * Drawing rules applied to the leaf layer, or an empty string when the
     * season needs none. These go into a <style> inside the SVG itself,
     * because the drawing is served to an <img> and has no page around it.
     */
    public function leafStyle(): string
    {
        return match ($this) {
            // fill-box makes each leaf grow about its own centre instead of
            // the canvas origin, so nothing drifts off the branch it hangs on.
            self::Summer => '.wc-leaves>g{transform-box:fill-box;transform-origin:center;'
                .'transform:scale(1.08)}',
            // A white shadow cast upwards lies along the top edge of whatever
            // shape it follows, which is where snow settles.
            // Snow lies on the branches too, by the same means: the trunk is
            // one silhouette, so the white follows every upward-facing edge
            // of it.
            //
            // The offsets are in the drawing's own units, and the woodland
            // page shows 881 of them in about 150 pixels, so anything thinner
            // than about 8 vanishes there.
            self::Winter => '.wc-leaves>g{filter:drop-shadow(0 -8px 0 #fff) drop-shadow(0 -2px 0 #fff)}'
                .'.wc-trunk{filter:drop-shadow(0 -7px 0 #fff)}',
            self::Spring, self::Autumn => '',
        };
    }

    /**
     * Drawing added among the leaves themselves, inside the leaf layer.
     *
     * In summer the tree is in blossom: a flower beside roughly two leaves in
     * three, set where the artist joined the leaf to its twig — the first
     * point of its outline — so every flower sits on a branch. The flowers
     * come with the leaves a member has earned, so a bare tree stays bare.
     *
     * Each flower is decided by its leaf's own id rather than by its place in
     * the list, so earning a new leaf adds blossom without moving any.
     */
    public function canopy(string $leaves): string
    {
        if ($this !== self::Summer) {
            return '';
        }

        preg_match_all(
            '/<g id="([^"]+)">\s*<path d="M(-?[\d.]+),(-?[\d.]+)/',
            $leaves,
            $anchors,
            PREG_SET_ORDER,
        );

        $svg = '';

        foreach ($anchors as [, $id, $x, $y]) {
            if ($this->fixed("bloom{$id}") < 0.34) {
                continue;
            }

            $svg .= $this->blossom(
                (float) $x + ($this->fixed("bx{$id}") - 0.5) * 16,
                (float) $y + ($this->fixed("by{$id}") - 0.5) * 16,
                $id,
            );
        }

        return $svg;
    }

    private function blossom(float $x, float $y, string $id): string
    {
        // Mostly the pale pink of dog rose, now and then almost white.
        $petal = $this->fixed("tint{$id}") < 0.7 ? '#f4cdd8' : '#fbe9ee';
        $turn = (int) ($this->fixed("turn{$id}") * 72);
        // About half a leaf across, which is what still reads as a flower when
        // the whole tree is drawn 160 pixels wide on a woodland card.
        $scale = 1.8 + $this->fixed("size{$id}") * 0.6;

        $petals = '';

        for ($k = 0; $k < 5; $k++) {
            $petals .= sprintf(
                '<circle cx="0" cy="-3.9" r="3.9" fill="%s" stroke="#b8687e" stroke-width="0.5" '
                .'transform="rotate(%d)"/>',
                $petal,
                $turn + $k * 72,
            );
        }

        // The outer group carries no transform of its own, on purpose. Summer's
        // growth rule sets the CSS transform property on every group in the
        // leaf layer, and a CSS transform replaces a transform attribute, so a
        // translate written on that group would be thrown away and the flower
        // drawn at the canvas origin, out of sight.
        return sprintf(
            '<g class="wc-blossom"><g transform="translate(%.1f,%.1f) scale(%.2f)">%s'
            .'<circle r="1.9" fill="#e9c542" stroke="#a8841a" stroke-width="0.4"/></g></g>',
            $x, $y, $scale, $petals,
        );
    }

    /**
     * What lies on the ground at the foot of the tree, drawn over the trunk so
     * that it banks up against it.
     *
     * The coordinates are the artwork's own: its ground shadow is an ellipse
     * centred on (829, 1152), 194 units across either way and 29 deep, which
     * is where the trunk meets the earth.
     *
     * Nothing here is random. The drawing is cached against an ETag, and a
     * random scatter would be a different image on every request, so no
     * browser could ever reuse one. Positions are either written out or drawn
     * from a hash of their index, which is the same every time.
     */
    public function ground(): string
    {
        return match ($this) {
            self::Spring => $this->underfoot(blades: 46, height: [0.6, 1.1]),
            self::Summer => $this->underfoot(blades: 58, height: [0.8, 1.45], flowers: true),
            self::Autumn => $this->fallenLeaves(),
            self::Winter => $this->snowHeap(),
        };
    }

    /**
     * Grass growing round the base, and in summer daisies and buttercups in
     * it. Everything is sorted by how far forward it stands, so nearer blades
     * overlap the ones behind them.
     *
     * @param  array{float, float}  $height  smallest and largest blade, as a scale
     */
    private function underfoot(int $blades, array $height, bool $flowers = false): string
    {
        $items = [];

        for ($i = 0; $i < $blades; $i++) {
            // A point inside the ground ellipse, spread evenly by area.
            $angle = $this->fixed("angle{$i}") * 2 * M_PI;
            $reach = sqrt($this->fixed("reach{$i}"));
            $x = 829 + cos($angle) * $reach * 212;
            $y = 1152 + sin($angle) * $reach * 26;

            $scale = $height[0] + $this->fixed("size{$i}") * ($height[1] - $height[0]);
            $lean = ($this->fixed("lean{$i}") - 0.5) * 34;
            $flip = $this->fixed("flip{$i}") < 0.5 ? -1 : 1;

            $items[] = [$y, sprintf(
                '<path d="M-3.5,0 C-3,-14 -1,-26 1.5,-40 C2.5,-26 4,-14 4,0 Z" fill="%s" '
                .'stroke="#0a3309" stroke-width="0.8" '
                .'transform="translate(%.1f,%.1f) rotate(%.1f) scale(%.2f,%.2f)"/>',
                $i % 3 === 0 ? '#8cab46' : '#6e9d03',
                $x, $y, $lean, $flip * $scale, $scale,
            )];
        }

        if ($flowers) {
            // x, y, stem length, scale
            foreach ([[700, 1160, 34, 1.0], [778, 1172, 26, 0.9], [906, 1168, 32, 1.05],
                [990, 1154, 26, 0.85], [846, 1178, 20, 0.9], [640, 1150, 22, 0.8]] as [$x, $y, $stem, $s]) {
                $items[] = [$y, $this->daisy($x, $y, $stem, $s)];
            }

            foreach ([[738, 1150, 40, 1.0], [872, 1150, 44, 0.95], [950, 1174, 28, 0.9],
                [664, 1170, 24, 0.85], [1030, 1166, 30, 0.9]] as [$x, $y, $stem, $s]) {
                $items[] = [$y, $this->buttercup($x, $y, $stem, $s)];
            }
        }

        usort($items, fn (array $a, array $b): int => $a[0] <=> $b[0]);

        return implode('', array_column($items, 1));
    }

    private function daisy(int $x, int $y, int $stem, float $scale): string
    {
        $petals = '';

        for ($k = 0; $k < 10; $k++) {
            $petals .= sprintf(
                '<ellipse cx="0" cy="-6.5" rx="2.4" ry="6" fill="#fff" stroke="#b9b29a" '
                .'stroke-width="0.5" transform="rotate(%d)"/>',
                $k * 36,
            );
        }

        return sprintf(
            '<g transform="translate(%d,%d)"><path d="M0,0 L0,%d" stroke="#4f7a1a" stroke-width="1.6"/>'
            .'<g transform="translate(0,%d) scale(%s)">%s'
            .'<circle r="3.4" fill="#e8b923" stroke="#9a7608" stroke-width="0.5"/></g></g>',
            $x, $y, -$stem, -$stem, $scale, $petals,
        );
    }

    private function buttercup(int $x, int $y, int $stem, float $scale): string
    {
        $petals = '';

        for ($k = 0; $k < 5; $k++) {
            $petals .= sprintf(
                '<circle cx="0" cy="-3.8" r="4.3" fill="#f3c623" stroke="#9a7608" '
                .'stroke-width="0.6" transform="rotate(%d)"/>',
                $k * 72,
            );
        }

        return sprintf(
            '<g transform="translate(%d,%d)"><path d="M0,0 L0,%d" stroke="#4f7a1a" stroke-width="1.6"/>'
            .'<g transform="translate(0,%d) scale(%s)">%s'
            .'<circle r="2.1" fill="#c99a12"/></g></g>',
            $x, $y, -$stem, -$stem, $scale, $petals,
        );
    }

    /**
     * A heap of snow piled against the trunk: lumpy along the top, highest
     * where it has banked up against the wood, with a cool shadow beneath.
     */
    private function snowHeap(): string
    {
        $heap = 'M560,1170 C590,1138 640,1116 690,1118 C720,1119 736,1128 760,1116 '
            .'C786,1098 806,1078 830,1076 C858,1074 878,1092 905,1102 '
            .'C930,1111 956,1108 986,1114 C1030,1122 1080,1143 1110,1170 '
            .'C1060,1190 610,1190 560,1170 Z';

        return sprintf('<path d="%s" fill="#dfe7ee" transform="translate(0,8)"/>', $heap)
            .sprintf('<path d="%s" fill="#fff"/>', $heap);
    }

    /** A fraction in [0, 1) that is the same for the same key on every request. */
    private function fixed(string $key): float
    {
        return crc32($key) / 4294967296;
    }

    /** Leaves that have come down, banked around the base. */
    private function fallenLeaves(): string
    {
        // x, y, rotation, scale. Densest near the trunk at x 842.
        $scatter = [
            [620, 1160, -18, 1.5], [664, 1150, 24, 1.7], [700, 1166, -9, 1.6],
            [734, 1144, 33, 1.8], [762, 1162, -27, 1.7], [790, 1140, 12, 1.9],
            [812, 1160, -6, 1.8], [838, 1134, 21, 1.9], [860, 1158, -31, 1.8],
            [886, 1140, 8, 1.9], [912, 1162, 27, 1.7], [940, 1146, -15, 1.8],
            [968, 1164, 19, 1.6], [998, 1150, -24, 1.7], [1030, 1162, 11, 1.5],
            [1060, 1156, -20, 1.4], [752, 1176, 4, 1.5], [826, 1178, -16, 1.6],
            [900, 1176, 29, 1.5], [976, 1178, -8, 1.4],
        ];

        $svg = '';

        foreach ($scatter as $i => [$x, $y, $angle, $scale]) {
            $svg .= sprintf(
                '<g transform="translate(%d,%d) rotate(%d) scale(%s)">'
                .'<path d="M-15,0 C-9,-7 9,-7 15,0 C9,7 -9,7 -15,0 Z" fill="%s" stroke="#5a3410" stroke-width="0.9"/>'
                .'<path d="M-13,0 L13,0" stroke="#5a3410" stroke-width="0.6"/>'
                .'</g>',
                $x, $y, $angle, $scale, $i % 2 === 0 ? '#c4651c' : '#d9a62e',
            );
        }

        return $svg;
    }
}
