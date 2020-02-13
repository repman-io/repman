<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\Organization;

use Buddy\Repman\Query\User\Model\Package;
use Buddy\Repman\Service\Dist;
use Buddy\Repman\Service\Dist\Storage;
use Buddy\Repman\Service\Dist\Storage\InMemoryStorage;
use Buddy\Repman\Service\Organization\PackageManager;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

final class PackageManagerTest extends TestCase
{
    private PackageManager $manager;

    protected function setUp(): void
    {
        $this->manager = new PackageManager(new InMemoryStorage(), __DIR__.'/../../../Resources');
    }

    public function testFindProvidersForPackage(): void
    {
        $providers = $this->manager->findProviders('buddy', [
            new Package('id', 'without-name'),
            new Package('id', 'https://github.com/buddy-works/repman', 'buddy-works/repman'),
            new Package('id', 'https://github.com/not-exist/missing', 'not-exist/missing'),
        ]);

        self::assertEquals(['buddy-works/repman' => ['1.2.3' => [
            'version' => '1.2.3',
            'dist' => [
                'type' => 'zip',
                'url' => '/path/to/reference.zip',
                'reference' => 'ac7dcaf888af2324cd14200769362129c8dd8550',
            ],
        ]]], $providers);
    }

    public function testReturnDistributionFilenameWhenExist(): void
    {
        /** @phpstan-var mixed $storage */
        $storage = $this->prophesize(Storage::class);
        $storage->has(Argument::type(Dist::class))->willReturn(true);
        $storage->download(Argument::cetera())->shouldNotBeCalled();
        $storage->filename(Argument::type(Dist::class))->willReturn(
            __DIR__.'/../../../Resources/buddy/dist/buddy-works/repman/1.2.3.0_ac7dcaf888af2324cd14200769362129c8dd8550.zip'
        );

        $manager = new PackageManager($storage->reveal(), __DIR__.'/../../../Resources');

        self::assertStringContainsString(
            '1.2.3.0_ac7dcaf888af2324cd14200769362129c8dd8550.zip',
            $manager->distFilename('buddy', 'buddy-works/repman', '1.2.3.0', 'ac7dcaf888af2324cd14200769362129c8dd8550', 'zip')->get()
        );
    }

    public function testDownloadDistribution(): void
    {
        $distFilepath = __DIR__.'/../../../Resources/buddy/dist/buddy-works/repman/1.2.3.0_ac7dcaf888af2324cd14200769362129c8dd8550.zip';

        /** @phpstan-var mixed $storage */
        $storage = $this->prophesize(Storage::class);
        $storage->has(Argument::type(Dist::class))->willReturn(false);
        $storage->filename(Argument::type(Dist::class))->willReturn($distFilepath);
        $storage->download('/path/to/reference.zip', Argument::type(Dist::class))
            ->shouldBeCalledOnce();

        $manager = new PackageManager($storage->reveal(), __DIR__.'/../../../Resources');

        self::assertStringContainsString(
            '1.2.3.0_ac7dcaf888af2324cd14200769362129c8dd8550.zip',
            $manager->distFilename('buddy', 'buddy-works/repman', '1.2.3.0', 'ac7dcaf888af2324cd14200769362129c8dd8550', 'zip')->get()
        );
    }
}
