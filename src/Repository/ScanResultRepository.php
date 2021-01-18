<?php

declare(strict_types=1);

namespace Buddy\Repman\Repository;

use Buddy\Repman\Entity\Organization\Package\ScanResult;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ScanResult|null find($id, $lockMode = null, $lockVersion = null)
 * @method ScanResult|null findOneBy(array $criteria, array $orderBy = null)
 * @method ScanResult[]    findAll()
 * @method ScanResult[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @extends ServiceEntityRepository<ScanResult>
 */
class ScanResultRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ScanResult::class);
    }

    public function add(ScanResult $result): void
    {
        $this->_em->persist($result);
        $this->_em->flush();
    }
}
