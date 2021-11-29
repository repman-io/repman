<?php

declare(strict_types=1);

namespace Buddy\Repman\Migrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200706181929 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE organization_package_version (id UUID NOT NULL, package_id UUID NOT NULL, version VARCHAR(255) NOT NULL, reference VARCHAR(255) NOT NULL, size INT NOT NULL, date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX version_package_id_idx ON organization_package_version (package_id)');
        $this->addSql('CREATE INDEX version_date_idx ON organization_package_version (date)');
        $this->addSql('CREATE UNIQUE INDEX package_version ON organization_package_version (package_id, version)');
        $this->addSql('COMMENT ON COLUMN organization_package_version.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN organization_package_version.package_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN organization_package_version.date IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE organization_package_version ADD CONSTRAINT FK_62DF469AF44CABFF FOREIGN KEY (package_id) REFERENCES organization_package (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE organization_package_version');
    }
}
