<?php

declare(strict_types=1);

namespace Buddy\Repman\Entity;

use Buddy\Repman\Service\Organization\AliasGenerator;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity(repositoryClass="Buddy\Repman\Repository\OrganizationRepository")
 */
class Organization
{
    /**
     * @ORM\Id
     * @ORM\Column(type="uuid", unique=true)
     */
    private UuidInterface $id;

    /**
     * @ORM\ManyToOne(targetEntity="Buddy\Repman\Entity\User", inversedBy="organizations")
     * @ORM\JoinColumn(nullable=false)
     */
    private User $owner;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private \DateTimeImmutable $createdAt;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $name;

    /**
     * @ORM\Column(type="string", unique=true, length=255)
     */
    private string $alias;

    public function __construct(UuidInterface $id, User $owner, string $name)
    {
        $this->id = $id;
        $this->setOwner($owner->addOrganization($this));
        $this->name = $name;
        $this->alias = self::generateAlias($this->name());
        $this->createdAt = new \DateTimeImmutable();
    }

    public function id(): UuidInterface
    {
        return $this->id;
    }

    public function setOwner(User $owner): self
    {
        $this->owner = $owner;

        return $this;
    }

    public function owner(): User
    {
        return $this->owner;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function alias(): string
    {
        return $this->alias;
    }

    public static function generateAlias(string $input): string
    {
        return (new AliasGenerator())->generate($input);
    }
}
