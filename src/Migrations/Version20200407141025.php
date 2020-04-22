<?php

declare(strict_types=1);

namespace Buddy\Repman\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200407141025 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->skipIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE proxy_package_download (package VARCHAR(255) NOT NULL, date TIMESTAMP(0) WITHOUT TIME ZONE, version VARCHAR(255) NOT NULL, ip VARCHAR(15) DEFAULT NULL, user_agent VARCHAR(255) DEFAULT NULL)');
        $this->addSql('CREATE INDEX proxy_package_idx ON proxy_package_download (package)');
        $this->addSql('CREATE INDEX proxy_download_date_idx ON proxy_package_download (date)');
    }

    public function down(Schema $schema): void
    {
        $this->skipIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE proxy_package_download');
    }
}
