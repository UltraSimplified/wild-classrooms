<?php

declare(strict_types=1);

use App\Services\Trees\SignRenderer;

beforeEach(function (): void {
    $this->renderer = app(SignRenderer::class);
});

it('puts the name on the signpost', function (): void {
    expect($this->renderer->render('Alice'))
        ->toContain('<svg')
        ->toContain('Alice')
        ->toEndWith('</tspan></text></g></svg>');
});

it('shortens a name to its first word', function (): void {
    // The old code called strpos(' ', $label) — arguments the wrong way round
    // — so it searched for the name inside a single space, always got false,
    // and never truncated. Long names ran off the end of the sign.
    expect($this->renderer->render('Alice Smith'))
        ->toContain('Alice')
        ->not->toContain('Alice Smith');
});

it('escapes a name that would otherwise break the svg', function (): void {
    // Member names are typed in by group owners, and went into the SVG raw.
    $svg = $this->renderer->render('<script>alert(1)</script>');

    expect($svg)->not->toContain('<script>')
        ->and($svg)->toContain('&lt;script&gt;');
});

it('escapes an ampersand in a name', function (): void {
    expect($this->renderer->render('Bill&Ben'))
        ->toContain('Bill&amp;Ben')
        ->not->toContain('Bill&Ben');
});
