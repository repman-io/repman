<?php

declare(strict_types=1);

namespace Buddy\Repman\Migrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200416172613 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE organization_member (id UUID NOT NULL, user_id UUID NOT NULL, organization_id UUID NOT NULL, role VARCHAR(15) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_756A2A8DA76ED395 ON organization_member (user_id)');
        $this->addSql('CREATE INDEX IDX_756A2A8D32C8A3DE ON organization_member (organization_id)');
        $this->addSql('CREATE UNIQUE INDEX user_organization ON organization_member (user_id, organization_id)');
        $this->addSql('COMMENT ON COLUMN organization_member.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN organization_member.user_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN organization_member.organization_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE organization_invitation (token VARCHAR(255) NOT NULL, organization_id UUID NOT NULL, email VARCHAR(180) NOT NULL, role VARCHAR(15) NOT NULL, PRIMARY KEY(token))');
        $this->addSql('CREATE INDEX IDX_1846F34D32C8A3DE ON organization_invitation (organization_id)');
        $this->addSql('CREATE UNIQUE INDEX email_organization ON organization_invitation (email, organization_id)');
        $this->addSql('COMMENT ON COLUMN organization_invitation.organization_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE organization_member ADD CONSTRAINT FK_756A2A8DA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE organization_member ADD CONSTRAINT FK_756A2A8D32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE organization_invitation ADD CONSTRAINT FK_1846F34D32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE organization_member');
        $this->addSql('DROP TABLE organization_invitation');
    }
}
