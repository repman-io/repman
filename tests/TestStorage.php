<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests;

use Symfony\Component\Filesystem\Filesystem;

/**
 * The directory the application reads and writes packages in during tests.
 *
 * It is a copy of tests/Resources rather than tests/Resources itself, because serving
 * and synchronizing packages writes to it: dists are downloaded, metadata is rewritten
 * under a new content hash, old releases are deleted. Pointed at the fixtures, those
 * writes land on tracked files, and a test failing between making a change and undoing
 * it leaves the checkout dirty and the next run working from someone else's leftovers.
 *
 * @see tests/bootstrap.php, which rebuilds it before the tests run
 */
final class TestStorage
{
    public static function path(string $relative = ''): string
    {
        return dirname(__DIR__).'/var/test-storage'.($relative !== '' ? '/'.ltrim($relative, '/') : '');
    }

    /**
     * Discards whatever the last run left behind and seeds the fixtures again, so every
     * run starts from the same state.
     */
    public static function reset(): void
    {
        $filesystem = new Filesystem();
        $filesystem->remove(self::path());
        $filesystem->mirror(__DIR__.'/Resources', self::path());
    }
}
