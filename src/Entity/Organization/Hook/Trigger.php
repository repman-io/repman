<?php

declare(strict_types=1);

namespace Buddy\Repman\Entity\Organization\Hook;

use Buddy\Repman\Entity\Organization\Hook;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity
 * @ORM\Table(name="organization_hook_trigger")
 */
class Trigger
{
    /** @var string[] */
    public const TRIGGER_TYPES = [
        'member.accept',
        'package.vulnerabilities.has',
        'package.update.failure',
        'package.new',
        'package.new.version',
        'package.download',
    ];
    /**
     * @ORM\Id
     * @ORM\Column(type="uuid", unique=true)
     */
    private UuidInterface $id;

    /**
     * @ORM\ManyToOne(targetEntity="Buddy\Repman\Entity\Organization\Hook", inversedBy="triggers")
     * @ORM\JoinColumn(nullable=false)
     */
    private Hook $hook;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $type;

    public function __construct(
        UuidInterface $id,
        string $type
    ) {
        $this->id = $id;
        $this->setType($type);
    }

    public function id(): UuidInterface
    {
        return $this->id;
    }

    public function setType(string $type): void
    {
        if (isset($this->type)) {
            throw new \RuntimeException('Can not change hook type');
        }

        if (!in_array($type, self::TRIGGER_TYPES)) {
            throw new \RuntimeException(
                sprintf('Hook Type %s does not exist. Available Hook Types are %s', $type, implode(', ', self::TRIGGER_TYPES))
            );
        }

        $this->type = $type;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function setHook(Hook $hook): void
    {
        if (isset($this->hook)) {
            throw new \RuntimeException('You can not change trigger hook');
        }

        $this->hook = $hook;
    }
}
