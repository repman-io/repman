<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service;

use Buddy\Repman\Service\Json;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class JsonTest extends TestCase
{
    /**
     * @param array<mixed> $data
     */
    #[DataProvider('decodeDataProvider')]
    public function testJsonDecode(array $data, string $json): void
    {
        self::assertEquals($data, Json::decode($json));
    }

    /**
     * @return \Iterator<(int | string), mixed>
     */
    public static function decodeDataProvider(): \Iterator
    {
        yield [[], ''];
        yield [[], 'invalid'];
        yield [['some' => 'data'], '{"some":"data"}'];
    }
}
