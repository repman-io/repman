<?php

declare(strict_types=1);

namespace Buddy\Repman\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Buddy\Repman\Repository\ConfigRepository")
 */
class Config
{
    /**
     * @ORM\Id
     * @ORM\Column(type="string", unique=true, length=255)
     */
    private string $key;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $value;

    public function __construct(string $key, string $value)
    {
        $this->key = $key;
        $this->value = $value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }
}
