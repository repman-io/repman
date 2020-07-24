<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Telemetry;

interface Endpoint
{
    public function send(Entry $entry): void;

    public function addTechnicalEmail(Email $email): void;

    public function removeTechnicalEmail(Email $email): void;
}
