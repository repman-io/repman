<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User\Model\Package;

final class Link
{
    public function __construct(private readonly string $type, private readonly string $target, private readonly string $constraint, private readonly ?string $targetPackageId = null)
    {
    }

    public function type(): string
    {
        return $this->type;
    }

    public function target(): string
    {
        return $this->target;
    }

    public function constraint(): string
    {
        return $this->constraint;
    }

    public function targetPackageId(): ?string
    {
        return $this->targetPackageId;
    }
}
