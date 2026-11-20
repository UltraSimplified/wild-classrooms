<?php

declare(strict_types=1);

namespace App\Services\Trees;

/**
 * Draws the wooden signpost that names a member beside their tree.
 */
final class SignRenderer
{
    public function render(string $label): string
    {
        return $this->asset('sign-head.svg')
            .e($this->firstWord($label))
            .$this->asset('sign-foot.svg');
    }

    /**
     * The sign is only wide enough for one word.
     *
     * The old code called strpos(' ', $label) with the arguments the wrong way
     * round, so it looked for the name inside a single space, always got false,
     * and never truncated anything — long names ran off the signpost.
     */
    private function firstWord(string $label): string
    {
        $label = trim($label);
        $space = strpos($label, ' ');

        return $space === false ? $label : substr($label, 0, $space);
    }

    private function asset(string $file): string
    {
        return (string) file_get_contents(resource_path('svg/'.$file));
    }
}
