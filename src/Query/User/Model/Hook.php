<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User\Model;

use Buddy\Repman\Query\User\Model\Hook\Trigger;

final class Hook
{
    private string $id;
    private string $url;
    private string $secret;

    /** @var Trigger[] */
    private array $triggers;

    public function __construct(
        string $id,
        string $url,
        string $secret,
        array $triggers
    ) {
        $this->id = $id;
        $this->url = $url;
        $this->secret = $secret;
        $this->triggers = $triggers;
    }

    /**
     * @return string
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function url(): string
    {
        return $this->url;
    }

    /**
     * @return string
     */
    public function secret(): string
    {
        return $this->secret;
    }

    /**
     * @return Trigger[]
     */
    public function triggers(): array
    {
        return $this->triggers;
    }
}
