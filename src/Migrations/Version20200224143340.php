<?php

declare(strict_types=1);

namespace Buddy\Repman\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200224143340 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE organization_package_download (id UUID NOT NULL, package_id UUID NOT NULL, date DATE NOT NULL, version VARCHAR(255) NOT NULL, ip VARCHAR(15) DEFAULT NULL, user_agent VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX package_id_idx ON organization_package_download (package_id)');
        $this->addSql('COMMENT ON COLUMN organization_package_download.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN organization_package_download.package_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN organization_package_download.date IS \'(DC2Type:date_immutable)\'');
        $this->addSql('ALTER TABLE "user" ALTER status DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE organization_package_download');
        $this->addSql('ALTER TABLE "user" ALTER status SET DEFAULT \'enabled\'');
    }
}
