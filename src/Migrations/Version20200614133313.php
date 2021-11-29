<?php

declare(strict_types=1);

namespace Buddy\Repman\Migrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200614133313 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql("INSERT INTO config (key, value)
VALUES ('local_authentication', (
    SELECT CASE
               WHEN value = 'enabled'
                   THEN 'login_and_registration'
               ELSE 'login_only'
               END
    FROM config
    WHERE key = 'user_registration'
))");
        $this->addSql("INSERT INTO config (key, value)
VALUES ('oauth_registration', (
    SELECT value
    FROM config
    WHERE key = 'user_registration'
))");
        $this->addSql("DELETE FROM config WHERE key = 'user_registration'");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql("INSERT INTO config (key, value)
VALUES ('user_registration', (
    SELECT CASE
               WHEN value = 'disabled'
                   THEN 'disabled'
               ELSE 'enabled'
               END
    FROM config
    WHERE key = 'local_authentication'
))");
        $this->addSql("DELETE FROM config WHERE key = 'local_authentication'");
        $this->addSql("DELETE FROM config WHERE key = 'oauth_registration'");
    }
}
