<?php

declare(strict_types=1);

namespace Buddy\Repman\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200205115758 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE organization_package (id UUID NOT NULL, organization_id UUID NOT NULL, name VARCHAR(255) NOT NULL, repository_url TEXT NOT NULL, description TEXT NOT NULL, latest_released_version VARCHAR(255) NOT NULL, latest_release_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN organization_package.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN organization_package.organization_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE organization_package ADD CONSTRAINT FK_DE68679532C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_DE68679532C8A3DE ON organization_package (organization_id)');
        $this->addSql('COMMENT ON COLUMN organization_package.latest_release_date IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DE6867955E237E06 ON organization_package (name)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP INDEX UNIQ_DE6867955E237E06');
        $this->addSql('ALTER TABLE organization_package DROP CONSTRAINT FK_DE68679532C8A3DE');
        $this->addSql('DROP INDEX IDX_DE68679532C8A3DE');
        $this->addSql('ALTER TABLE organization_package DROP organization_id');
        $this->addSql('DROP TABLE organization_package');
    }
}
