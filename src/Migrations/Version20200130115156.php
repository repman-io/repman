<?php

declare(strict_types=1);

namespace Buddy\Repman\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200130115156 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE organization (id UUID NOT NULL, owner_id UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, name VARCHAR(255) NOT NULL, alias VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C1EE637CE16C6B94 ON organization (alias)');
        $this->addSql('CREATE INDEX IDX_C1EE637C7E3C61F9 ON organization (owner_id)');
        $this->addSql('COMMENT ON COLUMN organization.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN organization.owner_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN organization.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE organization ADD CONSTRAINT FK_C1EE637C7E3C61F9 FOREIGN KEY (owner_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE organization DROP CONSTRAINT FK_C1EE637C7E3C61F9');
        $this->addSql('DROP TABLE organization');
    }
}
