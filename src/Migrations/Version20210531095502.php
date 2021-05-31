<?php

declare(strict_types=1);

namespace Buddy\Repman\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210531095502 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE organization_package_link DROP CONSTRAINT fk_4a06082932c8a3de');
        $this->addSql('DROP INDEX idx_4a06082932c8a3de');
        $this->addSql('ALTER TABLE organization_package_link DROP organization_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE organization_package_link ADD organization_id UUID NOT NULL');
        $this->addSql('COMMENT ON COLUMN organization_package_link.organization_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE organization_package_link ADD CONSTRAINT fk_4a06082932c8a3de FOREIGN KEY (organization_id) REFERENCES organization (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_4a06082932c8a3de ON organization_package_link (organization_id)');
    }
}
