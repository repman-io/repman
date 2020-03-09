<?php

declare(strict_types=1);

namespace Buddy\Repman\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200309074829 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE organization_package DROP CONSTRAINT fk_13baefd661a264a5');
        $this->addSql('DROP INDEX idx_13baefd661a264a5');
        $this->addSql('ALTER TABLE organization_package ADD metadata JSON NOT NULL DEFAULT \'[]\'');
        $this->addSql('ALTER TABLE organization_package ALTER metadata DROP DEFAULT ');
        $this->addSql('ALTER TABLE organization_package DROP oauth_token_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE organization_package ADD oauth_token_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE organization_package DROP metadata');
        $this->addSql('COMMENT ON COLUMN organization_package.oauth_token_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE organization_package ADD CONSTRAINT fk_13baefd661a264a5 FOREIGN KEY (oauth_token_id) REFERENCES user_oauth_token (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_13baefd661a264a5 ON organization_package (oauth_token_id)');
    }
}
