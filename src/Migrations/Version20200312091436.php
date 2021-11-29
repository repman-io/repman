<?php

declare(strict_types=1);

namespace Buddy\Repman\Migrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200312091436 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial Repman migrations';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE organization (id UUID NOT NULL, owner_id UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, name VARCHAR(255) NOT NULL, alias VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_c1ee637ce16c6b94 ON organization (alias)');
        $this->addSql('CREATE INDEX idx_c1ee637c7e3c61f9 ON organization (owner_id)');
        $this->addSql('COMMENT ON COLUMN organization.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN organization.owner_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN organization.created_at IS \'(DC2Type:datetime_immutable)\'');

        $this->addSql('CREATE TABLE organization_token (value VARCHAR(64) NOT NULL, organization_id UUID NOT NULL, name VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, last_used_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(value))');
        $this->addSql('CREATE INDEX idx_d1b047fc32c8a3de ON organization_token (organization_id)');
        $this->addSql('COMMENT ON COLUMN organization_token.organization_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN organization_token.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN organization_token.last_used_at IS \'(DC2Type:datetime_immutable)\'');

        $this->addSql('CREATE TABLE user_oauth_token (id UUID NOT NULL, user_id UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, type VARCHAR(9) NOT NULL, access_token VARCHAR(255) NOT NULL, refresh_token VARCHAR(255) DEFAULT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX token_type ON user_oauth_token (type, user_id)');
        $this->addSql('CREATE INDEX idx_712f82bfa76ed395 ON user_oauth_token (user_id)');
        $this->addSql('COMMENT ON COLUMN user_oauth_token.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN user_oauth_token.user_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN user_oauth_token.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN user_oauth_token.expires_at IS \'(DC2Type:datetime_immutable)\'');

        $this->addSql('CREATE TABLE "user" (id UUID NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, reset_password_token VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, reset_password_token_created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, email_confirmed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, email_confirm_token VARCHAR(255) NOT NULL, status VARCHAR(20) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_8d93d6495afeb9f9 ON "user" (email_confirm_token)');
        $this->addSql('CREATE UNIQUE INDEX uniq_8d93d649452c9ec5 ON "user" (reset_password_token)');
        $this->addSql('CREATE UNIQUE INDEX uniq_8d93d649e7927c74 ON "user" (email)');
        $this->addSql('COMMENT ON COLUMN "user".id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN "user".created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN "user".reset_password_token_created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN "user".email_confirmed_at IS \'(DC2Type:datetime_immutable)\'');

        $this->addSql('CREATE TABLE organization_package (id UUID NOT NULL, organization_id UUID NOT NULL, name VARCHAR(255) DEFAULT NULL, repository_url TEXT NOT NULL, description TEXT DEFAULT NULL, latest_released_version VARCHAR(255) DEFAULT NULL, latest_release_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, type VARCHAR(255) NOT NULL, last_sync_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, last_sync_error TEXT DEFAULT NULL, webhook_created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, metadata JSON NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX package_name ON organization_package (organization_id, name)');
        $this->addSql('CREATE INDEX IDX_13BAEFD632C8A3DE ON organization_package (organization_id)');
        $this->addSql('COMMENT ON COLUMN organization_package.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN organization_package.organization_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN organization_package.latest_release_date IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN organization_package.last_sync_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN organization_package.webhook_created_at IS \'(DC2Type:datetime_immutable)\'');

        $this->addSql('CREATE TABLE organization_package_download (id UUID NOT NULL, package_id UUID NOT NULL, date DATE NOT NULL, version VARCHAR(255) NOT NULL, ip VARCHAR(15) DEFAULT NULL, user_agent VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX package_id_idx ON organization_package_download (package_id)');
        $this->addSql('CREATE INDEX download_date_idx ON organization_package_download (date)');
        $this->addSql('COMMENT ON COLUMN organization_package_download.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN organization_package_download.package_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN organization_package_download.date IS \'(DC2Type:date_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE organization');
        $this->addSql('DROP TABLE organization_token');
        $this->addSql('DROP TABLE user_oauth_token');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('DROP TABLE organization_package');
        $this->addSql('DROP TABLE organization_package_download');
    }
}
