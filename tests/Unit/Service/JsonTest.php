<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service;

use Buddy\Repman\Service\Json;
use Iterator;
use PHPUnit\Framework\TestCase;

final class JsonTest extends TestCase
{
    /**
     * @param array<mixed> $data
     *
     * @dataProvider decodeDataProvider
     */
    public function testJsonDecode(array $data, string $json): void
    {
        $this->assertEquals($data, Json::decode($json));
    }

    /**
     * @return array<mixed>
     */
    public function decodeDataProvider(): Iterator
    {
        yield [[], ''];
        yield [[], 'invalid'];
        yield [['some' => 'data'], '{"some":"data"}'];
    }
}
