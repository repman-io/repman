<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Telemetry;

interface Endpoint
{
    public function send(Entry $entry): void;

    public function addTechnicalEmail(TechnicalEmail $email): void;

    public function removeTechnicalEmail(TechnicalEmail $email): void;
}
