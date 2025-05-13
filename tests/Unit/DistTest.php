<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit;

use Buddy\Repman\Service\Dist;
use PHPUnit\Framework\TestCase;

final class DistTest extends TestCase
{
    public function testVersionWithSlash(): void
    {
        $dist = new Dist('repo', 'package', 'dev-master/feature', '123456', 'zip');

        $this->assertSame(md5('dev-master/feature'), $dist->version());
    }

    public function testVersionDevMaster(): void
    {
        $dist = new Dist('repo', 'package', 'dev-master', '123456', 'zip');

        $this->assertSame('9999999-dev', $dist->version());
    }
}
