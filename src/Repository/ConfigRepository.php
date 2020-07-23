<?php

declare(strict_types=1);

namespace Buddy\Repman\Repository;

use Buddy\Repman\Entity\Config;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Config|null find($id, $lockMode = null, $lockVersion = null)
 * @method Config|null findOneBy(array $criteria, array $orderBy = null)
 * @method Config[]    findAll()
 * @method Config[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @extends ServiceEntityRepository<Config>
 */
class ConfigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Config::class);
    }

    /**
     * @param array<string,mixed> $values
     */
    public function change(array $values): void
    {
        foreach ($values as $key => $value) {
            $config = $this->findOneBy(['key' => $key]);

            if ($config instanceof Config) {
                $config->setValue((string) $value);
            }
        }
    }
}
