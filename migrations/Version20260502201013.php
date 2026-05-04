<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260502201013 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create tournament_session_team_player table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE tournament_session_team_player (
                id INT AUTO_INCREMENT NOT NULL,
                tournament_session_team_id INT NOT NULL,
                player_id INT NOT NULL,
                is_legionary TINYINT DEFAULT 0 NOT NULL,
                INDEX IDX_tstp_session_team (tournament_session_team_id),
                INDEX IDX_tstp_player (player_id),
                UNIQUE INDEX UQ_session_team_player (tournament_session_team_id, player_id),
                PRIMARY KEY (id),
                CONSTRAINT FK_7DB36F76D3B9DC96 FOREIGN KEY (tournament_session_team_id) REFERENCES tournament_session_team (id),
                CONSTRAINT FK_7DB36F7699E6F5DF FOREIGN KEY (player_id) REFERENCES player (id)
            ) DEFAULT CHARACTER SET utf8mb4
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE tournament_session_team_player');
    }
}
