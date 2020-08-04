<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Telemetry;

final class TechnicalEmail implements \JsonSerializable
{
    private string $email;
    private string $instanceId;

    public function __construct(string $email, string $instanceId)
    {
        $this->email = $email;
        $this->instanceId = $instanceId;
    }

    /**
     * @return array<string,string>
     */
    public function jsonSerialize(): array
    {
        return [
            'email' => $this->email,
            'instanceId' => $this->instanceId,
        ];
    }
}
