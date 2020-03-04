<?php

declare(strict_types=1);

namespace Buddy\Repman\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200304092905 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE user_oauth_token (id UUID NOT NULL, user_id UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, type VARCHAR(9) NOT NULL, value VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_712F82BFA76ED395 ON user_oauth_token (user_id)');
        $this->addSql('CREATE UNIQUE INDEX token_type ON user_oauth_token (type, user_id)');
        $this->addSql('COMMENT ON COLUMN user_oauth_token.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN user_oauth_token.user_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN user_oauth_token.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE user_oauth_token ADD CONSTRAINT FK_712F82BFA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE organization_package ADD oauth_token_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE organization_package ADD webhook_created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN organization_package.oauth_token_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN organization_package.webhook_created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE organization_package ADD CONSTRAINT FK_13BAEFD661A264A5 FOREIGN KEY (oauth_token_id) REFERENCES user_oauth_token (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_13BAEFD661A264A5 ON organization_package (oauth_token_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE organization_package DROP CONSTRAINT FK_13BAEFD661A264A5');
        $this->addSql('DROP TABLE user_oauth_token');
        $this->addSql('DROP INDEX IDX_13BAEFD661A264A5');
        $this->addSql('ALTER TABLE organization_package DROP oauth_token_id');
        $this->addSql('ALTER TABLE organization_package DROP webhook_created_at');
    }
}
