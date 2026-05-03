<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260502201010 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create tournament and tournament_official tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE tournament (
                id INT AUTO_INCREMENT NOT NULL,
                name VARCHAR(255) NOT NULL,
                season_id INT DEFAULT NULL,
                started_at DATETIME DEFAULT NULL,
                ended_at DATETIME DEFAULT NULL,
                tours_count INT DEFAULT NULL,
                questions_per_tour INT DEFAULT NULL,
                difficulty DOUBLE PRECISION DEFAULT NULL,
                true_dl DOUBLE PRECISION DEFAULT NULL,
                INDEX IDX_tournament_season (season_id),
                PRIMARY KEY (id),
                CONSTRAINT FK_tournament_season FOREIGN KEY (season_id) REFERENCES season (id)
            ) DEFAULT CHARACTER SET utf8mb4
        ');

        $this->addSql('
            CREATE TABLE tournament_official (
                id INT AUTO_INCREMENT NOT NULL,
                tournament_id INT NOT NULL,
                player_id INT NOT NULL,
                role VARCHAR(20) NOT NULL,
                INDEX IDX_to_tournament (tournament_id),
                INDEX IDX_to_player (player_id),
                UNIQUE INDEX UQ_tournament_player_role (tournament_id, player_id, role),
                PRIMARY KEY (id),
                CONSTRAINT FK_to_tournament FOREIGN KEY (tournament_id) REFERENCES tournament (id),
                CONSTRAINT FK_to_player FOREIGN KEY (player_id) REFERENCES player (id)
            ) DEFAULT CHARACTER SET utf8mb4
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE tournament_official');
        $this->addSql('DROP TABLE tournament');
    }
}
