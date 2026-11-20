<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Spotting;
use App\Models\Unit;
use App\Services\Trees\SignRenderer;
use App\Services\Trees\TreeRenderer;
use Illuminate\Http\Response;

/**
 * Serves the woodland artwork as SVG images.
 *
 * These stay plain image endpoints rather than Inertia pages: the browser
 * requests them with <img src>, and they are cacheable.
 */
class TreeController extends Controller
{
    public function __construct(
        private readonly TreeRenderer $trees,
        private readonly SignRenderer $signs,
    ) {}

    public function tree(?Unit $unit = null): Response
    {
        // With no member named, draw a finished tree. That is what the home
        // page shows a visitor who has not planted one yet.
        if ($unit === null) {
            return $this->svg($this->trees->renderComplete());
        }

        $spottings = Spotting::query()
            ->with(['challenge.category'])
            ->where('unit_id', $unit->getKey())
            ->get();

        return $this->svg($this->trees->render($spottings));
    }

    public function sign(Unit $unit): Response
    {
        return $this->svg($this->signs->render((string) $unit->title));
    }

    private function svg(string $svg): Response
    {
        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=300',
        ]);
    }
}
