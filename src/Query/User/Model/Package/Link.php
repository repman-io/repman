<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User\Model\Package;

final class Link
{
    private string $type;
    private string $target;
    private string $constraint;
    private ?string $targetPackageId;

    public function __construct(string $type, string $target, string $constraint, ?string $targetPackageId = null)
    {
        $this->type = $type;
        $this->target = $target;
        $this->constraint = $constraint;
        $this->targetPackageId = $targetPackageId;
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
