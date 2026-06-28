<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260628120001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace UQ_team_player_season with UQ_player_season on team_player table';
    }

    public function up(Schema $schema): void
    {
        // Remove duplicate player+season entries, keeping only the first one per player per season
        $this->addSql('
            DELETE tp FROM team_player tp
            INNER JOIN (
                SELECT MAX(id) AS keep_id, player_id, season_id
                FROM team_player
                GROUP BY player_id, season_id
                HAVING COUNT(*) > 1
            ) dups ON tp.player_id = dups.player_id
                AND tp.season_id = dups.season_id
                AND tp.id != dups.keep_id
        ');

        $this->addSql('
            ALTER TABLE team_player
                DROP INDEX UQ_team_player_season,
                ADD UNIQUE INDEX UQ_player_season (player_id, season_id)
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE team_player
                DROP INDEX UQ_player_season,
                ADD UNIQUE INDEX UQ_team_player_season (team_id, player_id, season_id)
        ');
    }
}
