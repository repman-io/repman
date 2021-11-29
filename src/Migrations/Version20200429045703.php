<?php

declare(strict_types=1);

namespace Buddy\Repman\Migrations;

use Buddy\Repman\Entity\Organization\Member;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Ramsey\Uuid\Uuid;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200429045703 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE organization_package ADD CONSTRAINT FK_13BAEFD632C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE organization_token ADD CONSTRAINT FK_D1B047FC32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE organization ADD CONSTRAINT FK_C1EE637C7E3C61F9 FOREIGN KEY (owner_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_oauth_token ADD CONSTRAINT FK_712F82BFA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        $organizations = $this->connection->fetchAllAssociative('SELECT id, owner_id FROM organization');
        foreach ($organizations as $organization) {
            $this->addSql('INSERT INTO organization_member (id, organization_id, user_id, role) VALUES (:id, :org_id, :user_id, :role)', [
                'id' => Uuid::uuid4()->toString(),
                'org_id' => $organization['id'],
                'user_id' => $organization['owner_id'],
                'role' => Member::ROLE_OWNER,
            ]);
        }

        $this->addSql('ALTER TABLE organization DROP CONSTRAINT FK_C1EE637C7E3C61F9');
        $this->addSql('DROP INDEX idx_c1ee637c7e3c61f9');
        $this->addSql('ALTER TABLE organization DROP owner_id');

        $this->addSql('ALTER TABLE organization_member DROP CONSTRAINT FK_756A2A8DA76ED395');
        $this->addSql('ALTER TABLE organization_member DROP CONSTRAINT FK_756A2A8D32C8A3DE');
        $this->addSql('ALTER TABLE organization_member ADD CONSTRAINT FK_756A2A8DA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE organization_member ADD CONSTRAINT FK_756A2A8D32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE organization_token DROP CONSTRAINT FK_D1B047FC32C8A3DE');
        $this->addSql('ALTER TABLE user_oauth_token DROP CONSTRAINT FK_712F82BFA76ED395');
        $this->addSql('ALTER TABLE organization_package DROP CONSTRAINT FK_13BAEFD632C8A3DE');

        $this->addSql('ALTER TABLE organization ADD owner_id UUID');
        $this->addSql('COMMENT ON COLUMN organization.owner_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE organization ADD CONSTRAINT FK_C1EE637C7E3C61F9 FOREIGN KEY (owner_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        $owners = $this->connection->fetchAllAssociative('SELECT organization_id, user_id FROM organization_member WHERE role = :role', ['role' => Member::ROLE_OWNER]);
        foreach ($owners as $owner) {
            $this->addSql('UPDATE organization SET owner_id = :owner_id WHERE id = :id', [
                'owner_id' => $owner['user_id'],
                'id' => $owner['organization_id'],
            ]);
        }

        $this->addSql('ALTER TABLE organization ALTER COLUMN owner_id SET NOT NULL');
        $this->addSql('ALTER TABLE organization DROP CONSTRAINT FK_C1EE637C7E3C61F9');
        $this->addSql('CREATE INDEX idx_c1ee637c7e3c61f9 ON organization (owner_id)');

        $this->addSql('ALTER TABLE organization_member DROP CONSTRAINT fk_756a2a8da76ed395');
        $this->addSql('ALTER TABLE organization_member DROP CONSTRAINT fk_756a2a8d32c8a3de');
        $this->addSql('ALTER TABLE organization_member ADD CONSTRAINT fk_756a2a8da76ed395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE organization_member ADD CONSTRAINT fk_756a2a8d32c8a3de FOREIGN KEY (organization_id) REFERENCES organization (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
