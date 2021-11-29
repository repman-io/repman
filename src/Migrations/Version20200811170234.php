<?php

declare(strict_types=1);

namespace Buddy\Repman\Migrations;

use Composer\Semver\VersionParser;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200811170234 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE organization_package_version ADD stability VARCHAR(255)');

        foreach ($this->connection->fetchAllAssociative('SELECT id, version FROM organization_package_version') as $data) {
            $this->addSql(
                'UPDATE organization_package_version SET stability = :stability WHERE id = :id',
                [
                    ':id' => $data['id'],
                    ':stability' => VersionParser::parseStability($data['version']),
                ]
            );
        }

        $this->addSql('ALTER TABLE organization_package_version ALTER COLUMN stability SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE organization_package_version DROP stability');
    }
}
