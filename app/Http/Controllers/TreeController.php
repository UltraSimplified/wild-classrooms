<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Spotting;
use App\Models\Unit;
use App\Services\Trees\TreeRenderer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Serves the woodland artwork as SVG images.
 *
 * These stay plain image endpoints rather than Inertia pages: the browser
 * asks for them with <img src>, and they are the same bytes for everyone
 * looking at the same member.
 */
class TreeController extends Controller
{
    public function __construct(
        private readonly TreeRenderer $trees,
    ) {}

    public function tree(Request $request, ?Unit $unit = null): Response
    {
        // With no member named, draw a finished tree. That is what the home
        // page shows a visitor who has not planted one yet.
        if ($unit === null) {
            return $this->svg($request, $this->trees->renderComplete());
        }

        $spottings = Spotting::query()
            ->with(['challenge.category'])
            ->where('unit_id', $unit->getKey())
            ->get();

        return $this->svg($request, $this->trees->render($spottings));
    }

    /**
     * Return the drawing, letting the browser reuse its copy only while the
     * drawing has not changed.
     *
     * A plain max-age would be wrong here: adding a spotting grows a leaf, and
     * the child who just added it would keep seeing the old tree until the
     * cache expired. An ETag means the browser still asks every time, but gets
     * an empty 304 back whenever nothing has changed.
     */
    private function svg(Request $request, string $svg): Response
    {
        $response = response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'no-cache, private',
        ]);

        $response->setEtag(md5($svg));
        $response->isNotModified($request);

        return $response;
    }
}
