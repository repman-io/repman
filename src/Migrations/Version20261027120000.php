<?php

declare(strict_types=1);

namespace Buddy\Repman\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261027120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Widen package link constraints: a "suggest" description longer than 255 characters failed the sync';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE organization_package_link ALTER COLUMN "constraint" TYPE TEXT');
    }

    /**
     * Only possible while every stored constraint still fits, see Version20260821120000.
     */
    public function down(Schema $schema): void
    {
        $tooLong = (int) $this->connection->fetchOne('SELECT count(*) FROM organization_package_link WHERE length("constraint") > 255');

        $this->abortIf($tooLong > 0, sprintf(
            '%d package link(s) no longer fit in VARCHAR(255). Revert the application code and leave the column as TEXT.',
            $tooLong
        ));

        $this->addSql('ALTER TABLE organization_package_link ALTER COLUMN "constraint" TYPE VARCHAR(255)');
    }
}
