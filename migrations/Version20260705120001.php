<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260705120001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create classic_captain_claim table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE classic_captain_claim (
                id INT AUTO_INCREMENT NOT NULL,
                player_id INT NOT NULL,
                team_id INT NOT NULL,
                status VARCHAR(20) NOT NULL,
                comment TEXT NOT NULL,
                moderator_comment TEXT DEFAULT NULL,
                created_at DATETIME NOT NULL,
                resolved_at DATETIME DEFAULT NULL,
                INDEX IDX_cc_player (player_id),
                INDEX IDX_cc_team (team_id),
                INDEX IDX_cc_status (status),
                PRIMARY KEY (id),
                CONSTRAINT FK_cc_player FOREIGN KEY (player_id) REFERENCES common_player (id),
                CONSTRAINT FK_cc_team FOREIGN KEY (team_id) REFERENCES classic_team (id)
            ) DEFAULT CHARACTER SET utf8mb4
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE classic_captain_claim');
    }
}
