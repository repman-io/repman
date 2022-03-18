<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Integration\MessageHandler\Organization;

use Buddy\Repman\Message\Organization\Package\Update;
use Buddy\Repman\Tests\Integration\IntegrationTestCase;

final class UpdateHandlerTest extends IntegrationTestCase
{
    public function testHandlePackageNotFoundWithoutError(): void
    {
        $exception = null;
        try {
            $this->dispatchMessage(new Update('e0ea4d32-4144-4a67-9310-6dae483a6377', 'test', 0, true));
        } catch (\Exception $exception) {
        }

        self::assertNull($exception);
    }
}
