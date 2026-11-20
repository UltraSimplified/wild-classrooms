<?php

declare(strict_types=1);

namespace App\Services\Groups;

use App\Models\Group;
use App\Models\Spotting;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;

/**
 * Reconciles a group's member list against the names submitted on the form.
 *
 * The old version walked the submitted array by index against the existing
 * members, updating in place, which meant reordering the form reassigned every
 * member's spottings to a different person. Members are matched by id here, so
 * a name change renames that member and nothing else moves.
 */
final class MemberSync
{
    private const MAX_MEMBERS = 10;

    /**
     * @param  list<array{id?: int|string|null, title?: string|null}>|list<string|null>  $submitted
     * @return array{created: int, renamed: int, deleted: int}
     */
    public function sync(Group $group, array $submitted): array
    {
        $existing = $group->units()->get()->keyBy(fn (Unit $unit): int => (int) $unit->getKey());

        $created = 0;
        $renamed = 0;
        $deleted = 0;
        $keptIds = [];
        $slots = 0;

        return DB::transaction(function () use (
            $group, $submitted, $existing, &$created, &$renamed, &$deleted, &$keptIds, &$slots
        ): array {
            foreach ($submitted as $entry) {
                if ($slots >= self::MAX_MEMBERS) {
                    break;
                }

                [$id, $title] = $this->parse($entry);
                $title = trim((string) $title);

                // A blank name in an existing slot means "remove this member";
                // a blank name in a new slot is just an unused row.
                if ($title === '') {
                    continue;
                }

                $slots++;

                if ($id !== null && $existing->has($id)) {
                    $unit = $existing->get($id);

                    if ($unit->title !== $title) {
                        $unit->update(['title' => $title]);
                        $renamed++;
                    }

                    $keptIds[] = $id;

                    continue;
                }

                $group->units()->create(['title' => $title]);
                $created++;
            }

            // Anything the form no longer lists is gone. Its spottings go with
            // it — including the photos, which would otherwise be orphaned.
            $removed = $existing->reject(
                fn (Unit $unit, int $id): bool => in_array($id, $keptIds, true)
            );

            if ($removed->isNotEmpty()) {
                $removedIds = $removed->keys()->all();

                // One delete for all of them rather than a query per member.
                Spotting::whereIn('unit_id', $removedIds)->delete();
                Unit::whereKey($removedIds)->delete();

                $deleted = count($removedIds);
            }

            return ['created' => $created, 'renamed' => $renamed, 'deleted' => $deleted];
        });
    }

    /**
     * Accepts either a bare name or an {id, title} pair.
     *
     * @return array{0: int|null, 1: string|null}
     */
    private function parse(mixed $entry): array
    {
        if (is_array($entry)) {
            $id = $entry['id'] ?? null;

            return [
                $id === null || $id === '' ? null : (int) $id,
                isset($entry['title']) ? (string) $entry['title'] : null,
            ];
        }

        return [null, is_string($entry) ? $entry : null];
    }
}
