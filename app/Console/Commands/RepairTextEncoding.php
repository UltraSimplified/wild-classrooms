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
 * The columns are read from the schema rather than listed here. An earlier
 * version carried a hand-written list and missed `spottings.picture`, where
 * the bad byte is inside a *filename* — the row pointed at
 * "Old Man\x92s Beard.jpg" while the file on disk is named with a proper UTF-8
 * apostrophe, so that photo had been broken on the live site for years. A list
 * only ever covers the columns someone thought of.
 *
 * Safe to run more than once: anything already valid UTF-8 is left alone.
 */
class RepairTextEncoding extends Command
{
    protected $signature = 'app:repair-text-encoding {--dry-run : Report what would change without writing}';

    protected $description = 'Convert legacy Windows-1252 text to UTF-8';

    /**
     * Framework, queue and Nova tables. They hold serialised payloads, tokens
     * and cache entries rather than anything a person typed, so there is
     * nothing here to repair and a rewrite could only do harm.
     *
     * @var list<string>
     */
    private const SKIP_TABLES = [
        'action_events', 'cache', 'cache_locks', 'failed_jobs', 'job_batches',
        'jobs', 'migrations', 'nova_field_attachments', 'nova_notifications',
        'nova_pending_field_attachments', 'passkeys', 'password_reset_tokens',
        'personal_access_tokens', 'sessions',
    ];

    /**
     * Credentials and secrets. Always ASCII, so they would never match anyway,
     * but they are named here so that no future schema change can draw them in.
     *
     * @var list<string>
     */
    private const SKIP_COLUMNS = [
        'password', 'remember_token', 'two_factor_secret',
        'two_factor_recovery_codes', 'payload', 'credential_id', 'public_key',
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $total = 0;

        foreach (Schema::getTableListing(schemaQualified: false) as $table) {
            if (in_array($table, self::SKIP_TABLES, true)) {
                continue;
            }

            $columns = $this->textColumns($table);

            if ($columns === [] || ! Schema::hasColumn($table, 'id')) {
                continue;
            }

            $repaired = $this->repairTable($table, $columns, $dryRun);

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
        $values = $total === 1 ? 'value' : 'values';
        $this->info($dryRun
            ? "{$total} {$values} would be repaired. Run without --dry-run to apply."
            : "Repaired {$total} {$values}.");

        return self::SUCCESS;
    }

    /**
     * Every column on the table that can hold text, straight from the schema.
     *
     * @return list<string>
     */
    private function textColumns(string $table): array
    {
        $columns = [];

        foreach (Schema::getColumns($table) as $column) {
            $type = strtolower($column['type_name']);

            // char/varchar/text/mediumtext/longtext/clob/json. Binary types
            // (blob, varbinary) are deliberately not matched: the bytes there
            // are not meant to be text and must not be rewritten.
            if (preg_match('/^(.*char.*|.*text|clob|json|string)$/', $type) !== 1) {
                continue;
            }

            if (in_array($column['name'], self::SKIP_COLUMNS, true)) {
                continue;
            }

            $columns[] = $column['name'];
        }

        return $columns;
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
