<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service;

use Buddy\Repman\Entity\Organization\Package;
use Buddy\Repman\Service\Dist;
use Buddy\Repman\Service\Dist\Storage;
use Buddy\Repman\Service\ReadmeExtractor;
use Buddy\Repman\Tests\Doubles\FakeDownloader;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class ReadmeExtractorTest extends TestCase
{
    public function testExtractReadmeMissingDist(): void
    {
        $subject = new ReadmeExtractor(
            new Storage(new FakeDownloader(),
            new Filesystem(new Local(sys_get_temp_dir().'/repman'))),
        );
        $package = new Package(Uuid::uuid4(), 'git', '');
        $subject->extractReadme($package, new Dist('', '', '', '', ''));

        self::assertNull($package->readme());
    }
}
