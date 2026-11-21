<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Images\WebpConverter;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Converts the images already on disk to WebP, as new uploads now are.
 *
 * Each converted image is written next to the original, its record is pointed
 * at it, and the original is moved out of public/ into
 * storage/app/private/webp-originals rather than deleted. Moving it matters:
 * the old site kept phone photos' EXIF, GPS position included, and a JPEG
 * left in public/uploads would still be served at its old address. The
 * manifest.csv there records every change, so any of it can be put back.
 *
 * Run app:repair-text-encoding first. A filename stored with a Windows-1252
 * byte does not match the file on disk until that has been repaired, so this
 * would report it as missing.
 *
 * Safe to run more than once: anything already WebP is left alone.
 */
class ConvertImagesToWebp extends Command
{
    use ConfirmableTrait;

    protected $signature = 'app:convert-images-to-webp
        {--dry-run : Report what would change without converting anything}
        {--force : Run in production without asking first}';

    protected $description = 'Convert stored images to WebP and point their records at the new files';

    /**
     * Every column that stores an image, with the longest edge it is shown
     * at: the same limits new uploads are held to.
     *
     * @var list<array{string, string, int}>
     */
    private const COLUMNS = [
        ['spottings', 'picture', 1200],
        ['groups', 'image', 800],
        ['challenges', 'image', 2400],
        ['categories', 'image', 2400],
        ['posts', 'featured_image', 2400],
        ['schools', 'logo', 2400],
        ['schools', 'featured_image', 2400],
    ];

    /** Where originals go, on the private `local` disk. */
    private const BACKUP = 'webp-originals';

    /**
     * Files already converted this run, by original path, so a file that
     * several records share is converted once and every record follows it.
     *
     * @var array<string, string>
     */
    private array $converted = [];

    private int $bytesBefore = 0;

    private int $bytesAfter = 0;

    /** @var resource|null */
    private $manifest = null;

    public function handle(WebpConverter $converter): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if (! $dryRun && ! $this->confirmToProceed()) {
            return self::FAILURE;
        }

        // Artisan keeps one instance per process, so a second call in the
        // same process would otherwise start with the first one's tallies.
        $this->converted = [];
        $this->bytesBefore = 0;
        $this->bytesAfter = 0;
        $this->closeManifest();

        $rows = [];
        $problems = 0;

        foreach (self::COLUMNS as [$table, $column, $maxEdge]) {
            $tally = ['converted' => 0, 'already' => 0, 'missing' => 0, 'unreadable' => 0];

            DB::table($table)->select(['id', $column])
                ->whereNotNull($column)->where($column, '!=', '')
                ->lazyById()
                ->each(function (object $row) use ($table, $column, $maxEdge, $converter, $dryRun, &$tally): void {
                    $outcome = $this->convert($table, $column, (int) $row->id, (string) $row->{$column}, $maxEdge, $converter, $dryRun);
                    $tally[$outcome]++;
                });

            $rows[] = ["{$table}.{$column}", ...array_values($tally)];
            $problems += $tally['missing'] + $tally['unreadable'];
        }

        $this->closeManifest();

        $this->table(['Column', 'Converted', 'Already WebP', 'Not found', 'Unreadable'], $rows);

        $this->report($dryRun, $problems);

        return self::SUCCESS;
    }

    /**
     * @return 'converted'|'already'|'missing'|'unreadable'
     */
    private function convert(string $table, string $column, int $id, string $value, int $maxEdge, WebpConverter $converter, bool $dryRun): string
    {
        if (strtolower($this->extension($value)) === WebpConverter::EXTENSION) {
            return 'already';
        }

        $source = $this->locate($value);

        // A file shared with a record already converted: follow it there.
        if ($source !== null && isset($this->converted[$source])) {
            $this->point($table, $column, $id, $value, $this->converted[$source], null, $dryRun);

            return 'converted';
        }

        if ($source === null || ! is_file($source)) {
            $this->problem("{$table} #{$id}: {$value} was not found");

            return 'missing';
        }

        try {
            $webp = $converter->convertFile($source, $maxEdge);
        } catch (Throwable $e) {
            $this->problem("{$table} #{$id}: {$value} could not be read ({$e->getMessage()})");

            return 'unreadable';
        }

        [$target, $newValue] = $this->target($source, $value);

        $this->bytesBefore += (int) filesize($source);
        $this->bytesAfter += strlen($webp);
        $this->converted[$source] = $newValue;

        if ($dryRun) {
            return 'converted';
        }

        File::put($target, $webp);
        $this->point($table, $column, $id, $value, $newValue, $this->backUp($source), $dryRun);

        return 'converted';
    }

    /**
     * Where a stored value lives on disk, by the same rules MediaUrl serves
     * it: "/uploads/…" straight from public/, anything else from under
     * public/storage. Null for a URL elsewhere, or a value that would reach
     * outside those two directories.
     */
    private function locate(string $value): ?string
    {
        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return null;
        }

        $path = str_starts_with($value, '/uploads/')
            ? public_path(ltrim($value, '/'))
            : public_path('storage/'.ltrim($value, '/'));

        $directory = realpath(dirname($path));

        if ($directory === false) {
            return null;
        }

        foreach (['uploads', 'storage'] as $root) {
            $allowed = realpath(public_path($root));

            if ($allowed !== false && ($directory === $allowed || str_starts_with($directory, $allowed.DIRECTORY_SEPARATOR))) {
                return $directory.DIRECTORY_SEPARATOR.$this->filename($path);
            }
        }

        return null;
    }

    /**
     * The new file beside the original, and the value that points at it.
     * "Snail.jpg" and "Snail.png" side by side cannot both become
     * "Snail.webp", so a clash gets a number.
     *
     * @return array{string, string}
     */
    private function target(string $source, string $value): array
    {
        $directory = dirname($source);
        $stem = $this->stem($this->filename($source));
        $name = $stem.'.'.WebpConverter::EXTENSION;

        for ($n = 2; is_file($directory.DIRECTORY_SEPARATOR.$name); $n++) {
            $name = "{$stem}-{$n}.".WebpConverter::EXTENSION;
        }

        $prefix = substr($value, 0, strlen($value) - strlen($this->filename($value)));

        return [$directory.DIRECTORY_SEPARATOR.$name, $prefix.$name];
    }

    /**
     * Move an original out of public/, keeping its path below public/ so
     * the manifest can say exactly where it came from.
     */
    private function backUp(string $source): string
    {
        $public = (string) realpath(public_path());
        $relative = ltrim(substr($source, strlen($public)), DIRECTORY_SEPARATOR);

        // public/storage may be the storage symlink, whose real path is
        // outside public/. File it under "storage" all the same.
        if (! str_starts_with($source, $public.DIRECTORY_SEPARATOR)) {
            $storage = (string) realpath(public_path('storage'));
            $relative = 'storage/'.ltrim(substr($source, strlen($storage)), DIRECTORY_SEPARATOR);
        }

        $backup = Storage::disk('local')->path(self::BACKUP.'/'.$relative);

        if (file_exists($backup)) {
            $backup .= '.'.time();
        }

        File::ensureDirectoryExists(dirname($backup));
        File::move($source, $backup);

        return $backup;
    }

    private function point(string $table, string $column, int $id, string $old, string $new, ?string $backup, bool $dryRun): void
    {
        if ($dryRun) {
            return;
        }

        // The query builder, not the model, so updated_at keeps its meaning.
        DB::table($table)->where('id', $id)->update([$column => $new]);

        $this->record([$table, $column, $id, $old, $new, $backup ?? '(shared with an earlier row)']);
    }

    /**
     * @param  list<string|int>  $fields
     */
    private function record(array $fields): void
    {
        if ($this->manifest === null) {
            $path = Storage::disk('local')->path(self::BACKUP.'/manifest.csv');
            File::ensureDirectoryExists(dirname($path));
            $new = ! is_file($path);

            $this->manifest = fopen($path, 'a') ?: null;

            if ($new && $this->manifest !== null) {
                fputcsv($this->manifest, ['table', 'column', 'id', 'old value', 'new value', 'original moved to'], escape: '');
            }
        }

        if ($this->manifest !== null) {
            fputcsv($this->manifest, $fields, escape: '');
        }
    }

    private function closeManifest(): void
    {
        if (is_resource($this->manifest)) {
            fclose($this->manifest);
        }

        $this->manifest = null;
    }

    private function problem(string $message): void
    {
        if ($this->output->isVerbose()) {
            $this->warn("  {$message}");
        }
    }

    private function report(bool $dryRun, int $problems): void
    {
        $count = count($this->converted);

        if ($count > 0) {
            $this->info(sprintf(
                '%s %d file%s: %s down to %s.',
                $dryRun ? 'Would convert' : 'Converted',
                $count,
                $count === 1 ? '' : 's',
                $this->megabytes($this->bytesBefore),
                $this->megabytes($this->bytesAfter),
            ));
        } else {
            $this->info('Nothing to convert.');
        }

        if ($problems > 0 && ! $this->output->isVerbose()) {
            $this->line("Run with -v to list the {$problems} that could not be converted; their records are unchanged.");
        }

        if ($dryRun) {
            $this->line('Dry run: nothing was changed. Run without --dry-run to apply.');
        } elseif ($count > 0) {
            $this->line('Originals and manifest.csv are in '.Storage::disk('local')->path(self::BACKUP).'.');
        }
    }

    /**
     * The last path segment. basename() depends on the locale and can mangle
     * a multibyte name, and some stored names have curly apostrophes.
     */
    private function filename(string $path): string
    {
        $slash = strrpos($path, '/');

        return $slash === false ? $path : substr($path, $slash + 1);
    }

    private function stem(string $filename): string
    {
        $dot = strrpos($filename, '.');

        return $dot === false || $dot === 0 ? $filename : substr($filename, 0, $dot);
    }

    private function extension(string $value): string
    {
        $filename = $this->filename($value);
        $dot = strrpos($filename, '.');

        return $dot === false ? '' : substr($filename, $dot + 1);
    }

    private function megabytes(int $bytes): string
    {
        return number_format($bytes / 1048576, 1).' MB';
    }
}
