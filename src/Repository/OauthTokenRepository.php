<?php

declare(strict_types=1);

namespace Buddy\Repman\Repository;

use Buddy\Repman\Entity\User\OauthToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method OauthToken|null find($id, $lockMode = null, $lockVersion = null)
 * @method OauthToken|null findOneBy(array $criteria, array $orderBy = null)
 * @method OauthToken[]    findAll()
 * @method OauthToken[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OauthTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OauthToken::class);
    }
}
