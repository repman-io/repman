<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service;

use Buddy\Repman\Service\Json;
use PHPUnit\Framework\TestCase;

final class JsonTest extends TestCase
{
    /**
     * @param array<mixed> $data
     * @dataProvider decodeDataProvider
     */
    public function testJsonDecode(array $data, string $json): void
    {
        self::assertEquals($data, Json::decode($json));
    }

    /**
     * @return array<mixed>
     */
    public function decodeDataProvider(): array
    {
        return [
            [[], ''],
            [[], 'invalid'],
            [['some' => 'data'], '{"some":"data"}'],
        ];
    }
}
