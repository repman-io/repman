<?php

declare(strict_types=1);

namespace Buddy\Repman\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240911143541 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add possibility to define which owner tokens should be used for OAuth requests';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE organization ADD oauth_owner_id UUID NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE organization DROP oauth_owner_id');
    }
}
