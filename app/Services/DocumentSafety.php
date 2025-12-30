<?php

namespace App\Services;

use Illuminate\Support\Str;

class DocumentSafety
{
    /**
     * Normalize a user-provided path to a safe relative path (no traversal).
     */
    public static function normalizeRelativePath(string $path): string
    {
        // Convert backslashes, remove null bytes, collapse dots
        $path = str_replace("\0", '', $path);
        $path = str_replace('\\', '/', $path);
        $parts = array_filter(explode('/', $path), static function ($part) {
            return $part !== '' && $part !== '.' && $part !== '..';
        });

        return implode('/', $parts);
    }

    /**
     * Ensure fullPath is within baseDir (prevents traversal). Returns bool.
     */
    public static function withinBase(string $baseDir, string $fullPath): bool
    {
        $base = realpath($baseDir);
        $real = realpath($fullPath);

        if ($base === false || $real === false) {
            return false;
        }

        // Append directory separator to avoid prefix tricks
        $base = rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $real = rtrim($real, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        return Str::startsWith($real, $base);
    }

    /**
     * Allowed file extensions for viewing/sync.
     *
     * @return string[]
     */
    public static function allowedExtensions(): array
    {
        return ['md', 'markdown', 'txt', 'pdf', 'docx', 'xlsx'];
    }

    public static function isAllowedExtension(string $ext): bool
    {
        return in_array(strtolower($ext), self::allowedExtensions(), true);
    }

    /**
     * Compute content hash (sha256) from string content.
     */
    public static function hashContent(string $content): string
    {
        return hash('sha256', $content);
    }

    /**
     * Compute file hash if readable; returns null on failure.
     */
    public static function hashFile(string $fullPath): ?string
    {
        if (!is_file($fullPath) || !is_readable($fullPath)) {
            return null;
        }
        return hash_file('sha256', $fullPath) ?: null;
    }
}
