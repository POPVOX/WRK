<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Documents are stored on the private "local" disk; files uploaded before
 * the move may still sit on the "public" disk, so reads fall back to it
 * and deletes clear both.
 */
class PrivateFiles
{
    public const DISK = 'local';

    public static function diskFor(string $path): ?string
    {
        foreach ([self::DISK, 'public'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return $disk;
            }
        }

        return null;
    }

    public static function absolutePath(string $path): ?string
    {
        $disk = self::diskFor($path);

        return $disk ? Storage::disk($disk)->path($path) : null;
    }

    public static function get(string $path): ?string
    {
        $disk = self::diskFor($path);

        return $disk ? Storage::disk($disk)->get($path) : null;
    }

    public static function delete(string $path): void
    {
        foreach ([self::DISK, 'public'] as $disk) {
            Storage::disk($disk)->delete($path);
        }
    }

    public static function download(string $path, ?string $name = null)
    {
        $disk = self::diskFor($path);

        abort_if($disk === null, 404);

        return Storage::disk($disk)->download($path, $name);
    }
}
