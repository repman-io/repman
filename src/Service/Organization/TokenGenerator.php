<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Organization;

interface TokenGenerator
{
    public function generate(): string;
}
