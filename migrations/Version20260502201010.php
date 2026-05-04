<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260502201010 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create tournament_official table';
    }

    public function up(Schema $schema): void
    {
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
                CONSTRAINT FK_78C9D4E833D1A3E7 FOREIGN KEY (tournament_id) REFERENCES tournament (id),
                CONSTRAINT FK_78C9D4E899E6F5DF FOREIGN KEY (player_id) REFERENCES player (id)
            ) DEFAULT CHARACTER SET utf8mb4
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE tournament_official');
    }
}
