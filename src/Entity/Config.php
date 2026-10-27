<?php

declare(strict_types=1);

namespace Buddy\Repman\Entity;

use Buddy\Repman\Repository\ConfigRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConfigRepository::class)]
class Config
{
    // rows are created by migrations and only updated here
    #[ORM\Id]
    #[ORM\Column(type: 'string', unique: true, length: 255)]
    private string $key; // @phpstan-ignore property.onlyRead

    #[ORM\Column(type: 'string', length: 255)]
    private string $value;

    public function setValue(string $value): void
    {
        $this->value = $value;
    }
}
