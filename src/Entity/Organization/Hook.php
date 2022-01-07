<?php

declare(strict_types=1);

namespace Buddy\Repman\Entity\Organization;

use Buddy\Repman\Entity\Organization;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity
 * @ORM\Table(name="organization_hook")
 */
class Hook
{
    /**
     * @ORM\Id
     * @ORM\Column(type="uuid", unique=true)
     */
    private UuidInterface $id;

    /**
     * @ORM\ManyToOne(targetEntity="Buddy\Repman\Entity\Organization", inversedBy="hooks")
     * @ORM\JoinColumn(nullable=false)
     */
    private Organization $organization;

    /**
     * @var Collection<int,Organization\Hook\Trigger>|Hook[]
     * @ORM\OneToMany(targetEntity="Buddy\Repman\Entity\Organization\Hook\Trigger", mappedBy="hook", cascade={"persist"}, orphanRemoval=true)
     */
    private Collection $triggers;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $url;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $secret;

    public function __construct(
        UuidInterface $id
    ) {
        $this->id = $id;
        $this->triggers = new ArrayCollection();
    }

    public function setOrganization(Organization $organization): void
    {
        if (isset($this->organization)) {
            throw new \RuntimeException('You can not change hook organization');
        }
        $this->organization = $organization;
    }

    public function isEqual(string $id): bool
    {
        return $this->id->toString() === $id;
    }

    public function addTrigger(Organization\Hook\Trigger $trigger): void
    {
        if ($this->triggers->contains($trigger)) {
            return;
        }

        $trigger->setHook($this);
        $this->triggers->add($trigger);
    }

    public function removeHook(string $uuid): void
    {
        foreach ($this->triggers as $trigger) {
            if ($trigger->isEqual($uuid)) {
                $this->triggers->removeElement($trigger);
            }
        }
    }

    /**
     * @return string
     */
    public function url(): string
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function secret(): string
    {
        return $this->secret;
    }

    /**
     * @param string $secret
     */
    public function setSecret(string $secret): void
    {
        $this->secret = $secret;
    }
}
