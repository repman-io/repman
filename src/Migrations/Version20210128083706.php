<?php

declare(strict_types=1);

namespace Buddy\Repman\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210128083706 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE organization_package ALTER keep_last_releases DROP DEFAULT');
        $this->addSql('ALTER TABLE "user" ALTER timezone DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE organization_package ALTER keep_last_releases SET DEFAULT 0');
        $this->addSql('ALTER TABLE "user" ALTER timezone SET DEFAULT \'Europe/Berlin\'');
    }
}
