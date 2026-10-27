<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\OAuth;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

final class BuddyResourceOwner implements ResourceOwnerInterface
{
    /**
     * @param mixed[] $data
     */
    public function __construct(private array $data)
    {
    }

    public function getId(): mixed
    {
        return $this->data['id'] ?? null;
    }

    /**
     * @return mixed[]
     */
    public function toArray(): array
    {
        return $this->data;
    }
}
