<?php

declare(strict_types=1);

namespace Buddy\Repman\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200416051700 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->skipIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE organization_package_webhook_request (package_id UUID NOT NULL, date TIMESTAMP(0) WITHOUT TIME ZONE, ip VARCHAR(15) DEFAULT NULL, user_agent VARCHAR(255) DEFAULT NULL)');
        $this->addSql('CREATE INDEX organization_package_webhook_request_package_idx ON organization_package_webhook_request (package_id)');
        $this->addSql('CREATE INDEX organization_package_webhook_request_date_idx ON organization_package_webhook_request (date)');
    }

    public function down(Schema $schema): void
    {
        $this->skipIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE organization_package_webhook_request');
    }
}
