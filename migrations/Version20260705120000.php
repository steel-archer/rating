<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260705120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create classic_team_player_transfer table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE classic_team_player_transfer (
                id INT AUTO_INCREMENT NOT NULL,
                player_id INT NOT NULL,
                team_id INT NOT NULL,
                season_id INT NOT NULL,
                type VARCHAR(10) NOT NULL,
                date DATE NOT NULL,
                INDEX IDX_tpt_player_season_date (player_id, season_id, date),
                PRIMARY KEY (id),
                CONSTRAINT FK_tpt_player FOREIGN KEY (player_id) REFERENCES common_player (id),
                CONSTRAINT FK_tpt_team FOREIGN KEY (team_id) REFERENCES classic_team (id),
                CONSTRAINT FK_tpt_season FOREIGN KEY (season_id) REFERENCES common_season (id)
            ) DEFAULT CHARACTER SET utf8mb4
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE classic_team_player_transfer');
    }
}
