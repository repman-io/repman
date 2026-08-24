<?php

declare(strict_types=1);

namespace Buddy\Repman\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260821120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Widen OAuth token columns, current Bitbucket tokens exceed 255 characters';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_oauth_token ALTER COLUMN access_token TYPE TEXT');
        $this->addSql('ALTER TABLE user_oauth_token ALTER COLUMN refresh_token TYPE TEXT');
    }

    /**
     * Only possible while every stored token still fits. Narrowing a column that holds
     * longer values aborts half way through, with Postgres' own "value too long" error,
     * on exactly the rows this migration was written for.
     *
     * Truncating them to fit instead is not an option: a shortened OAuth token still
     * looks like a token, authenticates as nothing, and the original is gone. Reverting
     * the application code needs no rollback anyway - it runs unchanged against TEXT.
     */
    public function down(Schema $schema): void
    {
        $tooLong = (int) $this->connection->fetchOne(
            'SELECT count(*) FROM user_oauth_token WHERE length(access_token) > 255 OR length(refresh_token) > 255'
        );

        $this->abortIf($tooLong > 0, sprintf(
            '%d stored OAuth token(s) no longer fit in VARCHAR(255). Rolling this migration back would have to discard part of them, which would silently break those integrations. Revert the application code and leave the columns as TEXT.',
            $tooLong
        ));

        $this->addSql('ALTER TABLE user_oauth_token ALTER COLUMN access_token TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE user_oauth_token ALTER COLUMN refresh_token TYPE VARCHAR(255)');
    }
}
