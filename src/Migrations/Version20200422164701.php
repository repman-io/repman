<?php

declare(strict_types=1);

namespace Buddy\Repman\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200422164701 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->skipIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE organization_invitation (token VARCHAR(255) NOT NULL, organization_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', email VARCHAR(180) NOT NULL, role VARCHAR(15) NOT NULL, INDEX IDX_1846F34D32C8A3DE (organization_id), UNIQUE INDEX email_organization (email, organization_id), PRIMARY KEY(token)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE organization_member (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', user_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', organization_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', role VARCHAR(15) NOT NULL, INDEX IDX_756A2A8DA76ED395 (user_id), INDEX IDX_756A2A8D32C8A3DE (organization_id), UNIQUE INDEX user_organization (user_id, organization_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE organization_package_download (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', package_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', date DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', version VARCHAR(255) NOT NULL, ip VARCHAR(15) DEFAULT NULL, user_agent VARCHAR(255) DEFAULT NULL, INDEX package_id_idx (package_id), INDEX download_date_idx (date), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE organization_package (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', organization_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', name VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, latest_released_version VARCHAR(255) DEFAULT NULL, latest_release_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', repository_url LONGTEXT NOT NULL, type VARCHAR(255) NOT NULL, last_sync_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', webhook_created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', last_sync_error LONGTEXT DEFAULT NULL, metadata LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', INDEX IDX_13BAEFD632C8A3DE (organization_id), UNIQUE INDEX package_name (organization_id, name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE organization_token (value VARCHAR(64) NOT NULL, organization_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', last_used_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_D1B047FC32C8A3DE (organization_id), PRIMARY KEY(value)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE organization (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', owner_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', name VARCHAR(255) NOT NULL, alias VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_C1EE637CE16C6B94 (alias), INDEX IDX_C1EE637C7E3C61F9 (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_oauth_token (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', user_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', type VARCHAR(9) NOT NULL, access_token VARCHAR(255) NOT NULL, refresh_token VARCHAR(255) DEFAULT NULL, expires_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_712F82BFA76ED395 (user_id), UNIQUE INDEX token_type (type, user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `user` (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', email VARCHAR(180) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', roles LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', password VARCHAR(255) NOT NULL, email_confirmed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', email_confirm_token VARCHAR(255) NOT NULL, reset_password_token VARCHAR(255) DEFAULT NULL, reset_password_token_created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', status VARCHAR(20) NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), UNIQUE INDEX UNIQ_8D93D6495AFEB9F9 (email_confirm_token), UNIQUE INDEX UNIQ_8D93D649452C9EC5 (reset_password_token), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE organization_invitation ADD CONSTRAINT FK_1846F34D32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE organization_member ADD CONSTRAINT FK_756A2A8DA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE organization_member ADD CONSTRAINT FK_756A2A8D32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE organization_package ADD CONSTRAINT FK_13BAEFD632C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE organization_token ADD CONSTRAINT FK_D1B047FC32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE organization ADD CONSTRAINT FK_C1EE637C7E3C61F9 FOREIGN KEY (owner_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE user_oauth_token ADD CONSTRAINT FK_712F82BFA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->skipIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE organization_invitation DROP FOREIGN KEY FK_1846F34D32C8A3DE');
        $this->addSql('ALTER TABLE organization_member DROP FOREIGN KEY FK_756A2A8D32C8A3DE');
        $this->addSql('ALTER TABLE organization_package DROP FOREIGN KEY FK_13BAEFD632C8A3DE');
        $this->addSql('ALTER TABLE organization_token DROP FOREIGN KEY FK_D1B047FC32C8A3DE');
        $this->addSql('ALTER TABLE organization_member DROP FOREIGN KEY FK_756A2A8DA76ED395');
        $this->addSql('ALTER TABLE organization DROP FOREIGN KEY FK_C1EE637C7E3C61F9');
        $this->addSql('ALTER TABLE user_oauth_token DROP FOREIGN KEY FK_712F82BFA76ED395');
        $this->addSql('DROP TABLE organization_invitation');
        $this->addSql('DROP TABLE organization_member');
        $this->addSql('DROP TABLE organization_package_download');
        $this->addSql('DROP TABLE organization_package');
        $this->addSql('DROP TABLE organization_token');
        $this->addSql('DROP TABLE organization');
        $this->addSql('DROP TABLE user_oauth_token');
        $this->addSql('DROP TABLE `user`');
    }
}
