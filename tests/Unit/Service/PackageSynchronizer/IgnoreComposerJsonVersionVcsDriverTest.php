<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\PackageSynchronizer;

use Buddy\Repman\Service\PackageSynchronizer\IgnoreComposerJsonVersionVcsDriver;
use Composer\Repository\Vcs\VcsDriverInterface;
use PHPUnit\Framework\TestCase;

final class IgnoreComposerJsonVersionVcsDriverTest extends TestCase
{
    public function testRemovesVersionFromComposerInformation(): void
    {
        $inner = $this->createMock(VcsDriverInterface::class);
        $inner->method('getComposerInformation')->with('abc123')->willReturn([
            'name' => 'buddy-works/example',
            'version' => '1.0.0',
            'description' => 'Example package',
        ]);

        $driver = new IgnoreComposerJsonVersionVcsDriver($inner);

        self::assertSame([
            'name' => 'buddy-works/example',
            'description' => 'Example package',
        ], $driver->getComposerInformation('abc123'));
    }

    public function testReturnsNonArrayComposerInformationUnchanged(): void
    {
        $inner = $this->createMock(VcsDriverInterface::class);
        $inner->method('getComposerInformation')->willReturn(null);

        $driver = new IgnoreComposerJsonVersionVcsDriver($inner);

        self::assertNull($driver->getComposerInformation('abc123'));
    }

    public function testDelegatesTagsAndUrl(): void
    {
        $inner = $this->createMock(VcsDriverInterface::class);
        $inner->method('getTags')->willReturn(['1.0.0' => 'aaa', '1.0.1' => 'bbb']);
        $inner->method('getUrl')->willReturn('https://example.com/repo.git');

        $driver = new IgnoreComposerJsonVersionVcsDriver($inner);

        self::assertSame(['1.0.0' => 'aaa', '1.0.1' => 'bbb'], $driver->getTags());
        self::assertSame('https://example.com/repo.git', $driver->getUrl());
    }
}
