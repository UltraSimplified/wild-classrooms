<?php

declare(strict_types=1);

namespace App\Services\Groups;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

/**
 * Stores a group's photo.
 *
 * The old code wrote these into public/storage while spotting photos went to
 * public/uploads, so group images sat in the directory Laravel reserves for
 * the storage symlink and were liable to be clobbered by storage:link.
 */
final class GroupImageStore
{
    private const DIRECTORY = 'uploads/groups';

    public function store(UploadedFile $file, string $groupTitle): string
    {
        $directory = public_path(self::DIRECTORY);
        File::ensureDirectoryExists($directory);

        $filename = time().'-'.Str::slug($groupTitle).'.'.($file->extension() ?: $file->getClientOriginalExtension());

        Image::decodePath($file->getRealPath())
            ->orient()
            ->scaleDown(800, 800)
            ->save($directory.'/'.$filename);

        return '/'.self::DIRECTORY.'/'.$filename;
    }
}
