<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260502201006 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create team_player table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE team_player (
                id INT AUTO_INCREMENT NOT NULL,
                team_id INT NOT NULL,
                player_id INT NOT NULL,
                season_id INT NOT NULL,
                is_captain TINYINT DEFAULT 0 NOT NULL,
                INDEX IDX_tp_team (team_id),
                INDEX IDX_tp_player (player_id),
                INDEX IDX_tp_season (season_id),
                UNIQUE INDEX UQ_team_player_season (team_id, player_id, season_id),
                PRIMARY KEY (id),
                CONSTRAINT FK_EE023DBC296CD8AE FOREIGN KEY (team_id) REFERENCES team (id),
                CONSTRAINT FK_EE023DBC99E6F5DF FOREIGN KEY (player_id) REFERENCES player (id),
                CONSTRAINT FK_EE023DBC4EC001D1 FOREIGN KEY (season_id) REFERENCES season (id)
            ) DEFAULT CHARACTER SET utf8mb4
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE team_player');
    }
}
