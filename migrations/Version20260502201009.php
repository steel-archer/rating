<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260502201009 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create tournament table';
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
                CONSTRAINT FK_BD5FB8D94EC001D1 FOREIGN KEY (season_id) REFERENCES season (id)
            ) DEFAULT CHARACTER SET utf8mb4
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE tournament');
    }
}
