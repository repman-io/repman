<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Doctrine;

use Doctrine\DBAL\Schema\PostgreSQLSchemaManager;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;

/**
 * @codeCoverageIgnore
 */
final class FixPostgreSQLDefaultSchemaListener
{
    public function postGenerateSchema(GenerateSchemaEventArgs $args): void
    {
        $schemaManager = $args
            ->getEntityManager()
            ->getConnection()
            ->createSchemaManager();
        if (!$schemaManager instanceof PostgreSQLSchemaManager) {
            return;
        }
        foreach ($schemaManager->getExistingSchemaSearchPaths() as $namespace) {
            if (!$args->getSchema()->hasNamespace($namespace)) {
                $args->getSchema()->createNamespace($namespace);
            }
        }
    }
}
