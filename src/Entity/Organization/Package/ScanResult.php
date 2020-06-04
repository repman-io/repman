<?php

declare(strict_types=1);

namespace Buddy\Repman\Entity\Organization\Package;

use Buddy\Repman\Entity\Organization\Package;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity(repositoryClass="Buddy\Repman\Repository\ScanResultRepository")
 * @ORM\Table(
 *     name="organization_package_scan_result",
 *     indexes={
 *      @Index(name="date_idx", columns={"date"})
 *     }
 * )
 */
class ScanResult
{
    const STATUS_PENDING = 'pending';
    const STATUS_OK = 'ok';
    const STATUS_WARNING = 'warning';
    const STATUS_ERROR = 'error';
    const STATUS_NOT_AVAILABLE = 'n/a';

    /**
     * @ORM\Id
     * @ORM\Column(type="uuid", unique=true)
     */
    private UuidInterface $id;

    /**
     * @ORM\ManyToOne(targetEntity="Buddy\Repman\Entity\Organization\Package")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private Package $package;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private \DateTimeImmutable $date;

    /**
     * @ORM\Column(type="string", length=7)
     */
    private string $status;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $version;

    /**
     * @var array<string,array<string,string>|string>
     * @ORM\Column(type="json")
     */
    private array $content = [];

    /**
     * @param array<string,array<string,string>|string> $content
     */
    public function __construct(UuidInterface $id, Package $package, \DateTimeImmutable $date, string $status, array $content)
    {
        $this->id = $id;
        $this->package = $package;
        $this->date = $date;
        $this->status = $status;
        $this->version = (string) $this->package->latestReleasedVersion();
        $this->content = $content;
    }
}
