<?php

declare(strict_types=1);

namespace App\Services\Groups;

use App\Services\Images\WebpConverter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Stores a group's photo as WebP.
 *
 * The old code wrote these into public/storage while spotting photos went to
 * public/uploads, so group images sat in the directory Laravel reserves for
 * the storage symlink and were liable to be clobbered by storage:link.
 */
final class GroupImageStore
{
    private const MAX_EDGE = 800;

    private const DIRECTORY = 'uploads/groups';

    public function __construct(private readonly WebpConverter $converter) {}

    public function store(UploadedFile $file, string $groupTitle): string
    {
        $directory = public_path(self::DIRECTORY);
        File::ensureDirectoryExists($directory);

        $filename = time().'-'.Str::slug($groupTitle).'.'.WebpConverter::EXTENSION;

        File::put($directory.'/'.$filename, $this->converter->convertFile($file->getPathname(), self::MAX_EDGE));

        return '/'.self::DIRECTORY.'/'.$filename;
    }
}
