<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Doubles;

use Buddy\Repman\Service\Proxy\MetadataProvider;
use Munus\Control\Option;

final class FakeMetadataProvider implements MetadataProvider
{
    /**
     * @var array<string,mixed>
     */
    private array $metadata = [];

    public function fromUrl(string $url, int $expireTime = 0): Option
    {
        if (!isset($this->metadata[$url])) {
            return Option::none();
        }

        return Option::some($this->metadata[$url]);
    }

    /**
     * @param array<mixed> $metadata
     */
    public function setMetadata(string $url, array $metadata): void
    {
        $this->metadata[$url] = $metadata;
    }
}
