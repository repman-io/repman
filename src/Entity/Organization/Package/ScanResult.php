<?php

declare(strict_types=1);

namespace Buddy\Repman\Entity\Organization\Package;

use Buddy\Repman\Entity\Organization\Package;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity(repositoryClass="Buddy\Repman\Repository\ScanResultRepository")
 *
 * @ORM\Table(
 *     name="organization_package_scan_result",
 *     indexes={
 *
 *      @Index(name="date_idx", columns={"date"})
 *     }
 * )
 */
class ScanResult
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_OK = 'ok';

    public const STATUS_WARNING = 'warning';

    public const STATUS_ERROR = 'error';

    public const STATUS_NOT_AVAILABLE = 'n/a';

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $version;

    /**
     * @param array<string,array<string,string>|string> $content
     */
    public function __construct(/**
     * @ORM\Id
     *
     * @ORM\Column(type="uuid", unique=true)
     */
        private UuidInterface $id, /**
     * @ORM\ManyToOne(targetEntity="Buddy\Repman\Entity\Organization\Package")
     *
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
        private Package $package, /**
     * @ORM\Column(type="datetime_immutable")
     */
        private DateTimeImmutable $date, /**
     * @ORM\Column(type="string", length=7)
     */
        private string $status, /**
     * @ORM\Column(type="json")
     */
        private array $content)
    {
        $this->version = (string) $this->package->latestReleasedVersion();
    }
}
