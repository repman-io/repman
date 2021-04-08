<?php

declare(strict_types=1);

namespace Buddy\Repman\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210309201702 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add package links';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs

        $this->addSql('CREATE TABLE organization_package_link (id UUID NOT NULL, organization_id UUID NOT NULL, package_id UUID NOT NULL, target VARCHAR(255) NOT NULL, "constraint" VARCHAR(255) NOT NULL, type VARCHAR (255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX link_package_id_idx ON organization_package_link (package_id)');
        $this->addSql('CREATE INDEX IDX_4A06082932C8A3DE ON organization_package_link (organization_id)');
        $this->addSql('CREATE INDEX link_target_idx ON organization_package_link (target)');
        $this->addSql('COMMENT ON COLUMN organization_package_link.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN organization_package_link.package_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN organization_package_link.organization_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE organization_package_link ADD CONSTRAINT FK_CAKE4LIFE FOREIGN KEY (package_id) REFERENCES organization_package (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE organization_package_link ADD CONSTRAINT FK_4A06082932C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

        $this->addSql('DROP TABLE organization_package_link');
    }
}
