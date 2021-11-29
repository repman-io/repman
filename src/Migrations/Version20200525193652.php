<?php

declare(strict_types=1);

namespace Buddy\Repman\Migrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200525193652 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE organization_package_scan_result (id UUID NOT NULL, package_id UUID NOT NULL, date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, status VARCHAR(7) NOT NULL, version VARCHAR(255) NOT NULL, content JSON NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_9AB3F43AF44CABFF ON organization_package_scan_result (package_id)');
        $this->addSql('CREATE INDEX date_idx ON organization_package_scan_result (date)');
        $this->addSql('COMMENT ON COLUMN organization_package_scan_result.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN organization_package_scan_result.package_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN organization_package_scan_result.date IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE organization_package_scan_result ADD CONSTRAINT FK_9AB3F43AF44CABFF FOREIGN KEY (package_id) REFERENCES organization_package (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE organization_package ADD last_scan_result JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE organization_package ADD last_scan_status VARCHAR(7) DEFAULT NULL');
        $this->addSql('ALTER TABLE organization_package ADD last_scan_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN organization_package.last_scan_date IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE organization_package_scan_result');
        $this->addSql('ALTER TABLE organization_package DROP last_scan_result');
        $this->addSql('ALTER TABLE organization_package DROP last_scan_status');
        $this->addSql('ALTER TABLE organization_package DROP last_scan_date');
    }
}
