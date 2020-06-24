<?php

declare(strict_types=1);

namespace Buddy\Repman\Repository;

use Buddy\Repman\Entity\Organization;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Ramsey\Uuid\UuidInterface;

/**
 * @method Organization|null find($id, $lockMode = null, $lockVersion = null)
 * @method Organization|null findOneBy(array $criteria, array $orderBy = null)
 * @method Organization[]    findAll()
 * @method Organization[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @extends ServiceEntityRepository<Organization>
 */
class OrganizationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Organization::class);
    }

    public function getById(UuidInterface $id): Organization
    {
        $organization = $this->find($id);
        if (!$organization instanceof Organization) {
            throw new \InvalidArgumentException(sprintf('Organization with id %s not found.', $id->toString()));
        }

        return $organization;
    }

    public function getByInvitationToken(string $token): Organization
    {
        $organization = $this->createQueryBuilder('o')
            ->join('o.invitations', 'i')
            ->where('i.token = :token')
            ->setParameter('token', $token)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if (!$organization instanceof Organization) {
            throw new \InvalidArgumentException(sprintf('Organization with invitation token %s not found.', $token));
        }

        return $organization;
    }

    public function add(Organization $organization): void
    {
        $this->_em->persist($organization);
    }

    public function remove(UuidInterface $id): void
    {
        $this->_em->remove($this->getById($id));
    }
}
