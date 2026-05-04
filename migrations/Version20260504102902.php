<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260504102902 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create player_claim table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE player_claim (
                id INT AUTO_INCREMENT NOT NULL,
                user_id INT NOT NULL,
                player_id INT DEFAULT NULL,
                town_id INT DEFAULT NULL,
                first_name VARCHAR(255) DEFAULT NULL,
                last_name VARCHAR(255) NOT NULL,
                patronymic VARCHAR(255) DEFAULT NULL,
                status VARCHAR(20) NOT NULL,
                INDEX IDX_player_claim_user (user_id),
                INDEX IDX_player_claim_player (player_id),
                INDEX IDX_player_claim_town (town_id),
                PRIMARY KEY (id),
                CONSTRAINT FK_player_claim_user FOREIGN KEY (user_id) REFERENCES `user` (id),
                CONSTRAINT FK_player_claim_player FOREIGN KEY (player_id) REFERENCES player (id),
                CONSTRAINT FK_player_claim_town FOREIGN KEY (town_id) REFERENCES town (id)
            ) DEFAULT CHARACTER SET utf8mb4
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE player_claim');
    }
}
