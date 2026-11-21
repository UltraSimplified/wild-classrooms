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
     * What lies on the ground at the foot of the tree, drawn over the trunk so
     * that it banks up against it.
     *
     * The coordinates are the artwork's own: its ground shadow is centred near
     * (840, 1150), which is where the trunk meets the earth.
     */
    public function ground(): string
    {
        return match ($this) {
            self::Spring, self::Summer => '',
            self::Autumn => $this->fallenLeaves(),
            self::Winter => '<path d="M596,1164 C640,1124 760,1110 842,1112 C934,1110 1040,1124 1090,1164 '
                .'C1040,1192 650,1192 596,1164 Z" fill="#dfe7ee"/>'
                .'<path d="M604,1158 C648,1120 762,1104 842,1106 C930,1104 1034,1118 1082,1158 '
                .'C1030,1180 656,1180 604,1158 Z" fill="#fff"/>',
        };
    }

    /**
     * Leaves that have come down, banked around the base.
     *
     * The positions are written out rather than generated, because the drawing
     * is cached against an ETag: a random scatter would be a different image
     * on every request and no browser would ever reuse one.
     */
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
