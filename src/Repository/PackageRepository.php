<?php

declare(strict_types=1);

namespace Buddy\Repman\Repository;

use Buddy\Repman\Entity\Organization\Package;
use Buddy\Repman\Entity\Organization\Package\Download;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Ramsey\Uuid\UuidInterface;

/**
 * @method Package|null find($id, $lockMode = null, $lockVersion = null)
 * @method Package|null findOneBy(array $criteria, array $orderBy = null)
 * @method Package[]    findAll()
 * @method Package[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @extends ServiceEntityRepository<Package>
 */
class PackageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Package::class);
    }

    public function getById(UuidInterface $id): Package
    {
        $package = $this->find($id);
        if (!$package instanceof Package) {
            throw new \InvalidArgumentException(sprintf('Package %s not found.', $id->toString()));
        }

        return $package;
    }

    public function packageExist(string $name, UuidInterface $organizationId): bool
    {
        return false !== $this->_em->getConnection()->fetchOne('SELECT id FROM organization_package WHERE name = :name AND organization_id = :organizationId', [
            'name' => $name,
            'organizationId' => $organizationId->toString(),
        ]);
    }

    public function addDownload(Download $download): void
    {
        $this->_em->persist($download);
    }
}
