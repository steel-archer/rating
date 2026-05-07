<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260502201023 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add estimated_teams, created_at, updated_at to tournament_session';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE tournament_session
            DROP INDEX UQ_tournament_venue,
            ADD estimated_teams INT DEFAULT NULL,
            ADD created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            ADD updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            ADD INDEX IDX_ts_created_at (created_at),
            ADD INDEX IDX_ts_updated_at (updated_at)
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE tournament_session
            DROP INDEX IDX_ts_created_at,
            DROP INDEX IDX_ts_updated_at,
            DROP estimated_teams,
            DROP created_at,
            DROP updated_at,
            ADD UNIQUE INDEX UQ_tournament_venue (tournament_id, venue_id)
        ');
    }
}
