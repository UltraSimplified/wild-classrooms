<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Repairs text that was stored as Windows-1252 in a UTF-8 column.
 *
 * Content pasted from Word over the years arrived as Windows-1252 and was
 * written to the database byte for byte, so a curly apostrophe is a lone 0x92
 * rather than the three bytes UTF-8 needs. "Old Man's Beard" is the clearest
 * example. The old Blade site rendered those as a replacement character; the
 * Inertia one cannot render the page at all, because json_encode refuses
 * invalid UTF-8 and the client then has no page object to mount.
 *
 * Safe to run more than once: anything already valid UTF-8 is left alone.
 */
class RepairTextEncoding extends Command
{
    protected $signature = 'app:repair-text-encoding {--dry-run : Report what would change without writing}';

    protected $description = 'Convert legacy Windows-1252 text to UTF-8';

    /**
     * The text columns worth checking, by table.
     *
     * @var array<string, list<string>>
     */
    private const COLUMNS = [
        'challenges' => ['title', 'plural', 'teaser', 'description', 'where_to_find_them', 'credit', 'image_name'],
        'categories' => ['title'],
        'spottings' => ['notes', 'owner', 'location', 'comment', 'campaign'],
        'groups' => ['title', 'description', 'address_line_1', 'address_line_2', 'city', 'county'],
        'units' => ['title', 'description'],
        'posts' => ['title', 'intro', 'content'],
        'users' => ['name'],
        'schools' => ['school', 'statement', 'head', 'address_line_1', 'city'],
        'classrooms' => ['teacher'],
        'static_pages' => ['name', 'title', 'attributes'],
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $total = 0;

        foreach (self::COLUMNS as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $present = array_values(array_filter(
                $columns,
                fn (string $column): bool => Schema::hasColumn($table, $column)
            ));

            if ($present === []) {
                continue;
            }

            $repaired = $this->repairTable($table, $present, $dryRun);

            if ($repaired > 0) {
                $this->line(sprintf('  %-14s %d value%s', $table, $repaired, $repaired === 1 ? '' : 's'));
                $total += $repaired;
            }
        }

        if ($total === 0) {
            $this->info('All text is already valid UTF-8.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->info($dryRun
            ? "{$total} values would be repaired. Run without --dry-run to apply."
            : "Repaired {$total} values.");

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $columns
     */
    private function repairTable(string $table, array $columns, bool $dryRun): int
    {
        $repaired = 0;

        /*
         * Updated a row at a time on purpose. Each row needs its own set of
         * values, and upsert() is an INSERT underneath, so it would demand
         * every non-nullable column on the table rather than the handful of
         * text ones being repaired. This runs once, over roughly ninety rows.
         */
        $repair = function (string $value): ?string {
            if ($value === '' || mb_check_encoding($value, 'UTF-8')) {
                return null;
            }

            $fixed = mb_convert_encoding($value, 'UTF-8', 'Windows-1252');

            // Only accept a conversion that actually produces valid UTF-8;
            // leave anything stranger untouched.
            return mb_check_encoding($fixed, 'UTF-8') ? $fixed : null;
        };

        DB::table($table)->select(['id', ...$columns])->orderBy('id')
            ->chunk(200, function ($rows) use ($table, $columns, $dryRun, $repair, &$repaired): void {
                foreach ($rows as $row) {
                    $changes = [];

                    foreach ($columns as $column) {
                        $value = $row->{$column};

                        if (is_string($value) && ($fixed = $repair($value)) !== null) {
                            $changes[$column] = $fixed;
                        }
                    }

                    if ($changes === []) {
                        continue;
                    }

                    $repaired += count($changes);

                    if (! $dryRun) {
                        DB::table($table)->where('id', $row->id)->update($changes);
                    }
                }
            });

        return $repaired;
    }
}
