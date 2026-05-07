<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260502201024 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create session_claim table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE session_claim (
                id INT AUTO_INCREMENT NOT NULL,
                session_id INT NOT NULL,
                player_id INT NOT NULL,
                status VARCHAR(20) NOT NULL,
                comment LONGTEXT DEFAULT NULL,
                created_at DATETIME NOT NULL,
                resolved_at DATETIME DEFAULT NULL,
                UNIQUE INDEX UNIQ_sc_session (session_id),
                INDEX IDX_sc_player (player_id),
                INDEX IDX_sc_created_at (created_at),
                CONSTRAINT FK_sc_session FOREIGN KEY (session_id)
                    REFERENCES tournament_session (id) ON DELETE CASCADE,
                CONSTRAINT FK_sc_player FOREIGN KEY (player_id)
                    REFERENCES player (id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE session_claim');
    }
}
