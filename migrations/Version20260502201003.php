<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260502201003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create player table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE player (
                id INT AUTO_INCREMENT NOT NULL,
                last_name VARCHAR(255) NOT NULL,
                first_name VARCHAR(255) NOT NULL,
                patronymic VARCHAR(255) DEFAULT NULL,
                town_id INT DEFAULT NULL,
                INDEX IDX_player_town (town_id),
                PRIMARY KEY (id),
                CONSTRAINT FK_98197A6575E23604 FOREIGN KEY (town_id) REFERENCES town (id)
            ) DEFAULT CHARACTER SET utf8mb4
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE player');
    }
}
