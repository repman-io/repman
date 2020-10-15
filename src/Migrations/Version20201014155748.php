<?php

declare(strict_types=1);

namespace Buddy\Repman\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201014155748 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Readme to Packages';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "organization_package" ADD readme text default null');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "organization_package" DROP readme');
    }
}
