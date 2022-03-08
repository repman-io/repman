<?php

declare(strict_types=1);

namespace Buddy\Repman\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220220163835 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add replacement_package on package table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE organization_package ADD replacement_package TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE organization_package DROP replacement_package');
    }
}
