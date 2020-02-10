<?php

declare(strict_types=1);

namespace Buddy\Repman\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200210134334 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP INDEX UNIQ_DE6867955E237E06');
        $this->addSql('ALTER TABLE organization_package ADD type VARCHAR(255) NOT NULL DEFAULT \'vcs\'');
        $this->addSql('ALTER TABLE organization_package ALTER type DROP DEFAULT');
        $this->addSql('ALTER TABLE organization_package ADD last_sync_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE organization_package ALTER name DROP NOT NULL');
        $this->addSql('ALTER TABLE organization_package ALTER description DROP NOT NULL');
        $this->addSql('ALTER TABLE organization_package ALTER latest_released_version DROP NOT NULL');
        $this->addSql('ALTER TABLE organization_package ALTER latest_release_date DROP NOT NULL');
        $this->addSql('COMMENT ON COLUMN organization_package.last_sync_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE UNIQUE INDEX package_name ON organization_package (organization_id, name)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP INDEX package_name');
        $this->addSql('ALTER TABLE organization_package DROP type');
        $this->addSql('ALTER TABLE organization_package DROP last_sync_at');
        $this->addSql('ALTER TABLE organization_package ALTER name SET NOT NULL');
        $this->addSql('ALTER TABLE organization_package ALTER description SET NOT NULL');
        $this->addSql('ALTER TABLE organization_package ALTER latest_released_version SET NOT NULL');
        $this->addSql('ALTER TABLE organization_package ALTER latest_release_date SET NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DE6867955E237E06 ON organization_package (name)');
    }
}
