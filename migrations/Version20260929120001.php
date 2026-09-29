<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260929120001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create classic_tournament_session_host_history and backfill from approved/revoked sessions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE classic_tournament_session_host_history (
                id INT AUTO_INCREMENT NOT NULL,
                session_id INT NOT NULL,
                player_id INT NOT NULL,
                created_at DATETIME NOT NULL,
                INDEX IDX_tshh_session (session_id),
                INDEX IDX_tshh_player (player_id),
                UNIQUE INDEX UNIQ_tshh_session_player (session_id, player_id),
                PRIMARY KEY (id),
                CONSTRAINT FK_tshh_session
                    FOREIGN KEY (session_id) REFERENCES classic_tournament_session (id),
                CONSTRAINT FK_tshh_player
                    FOREIGN KEY (player_id) REFERENCES common_player (id)
            ) DEFAULT CHARACTER SET utf8mb4
        ');

        // Backfill: every session whose claim has ever been approved (approved now,
        // or revoked after an approval) gave its current host access to the package,
        // so it must appear in the history.
        $this->addSql("
            INSERT INTO classic_tournament_session_host_history (session_id, player_id, created_at)
            SELECT ts.id, ts.host_id, NOW()
            FROM classic_tournament_session ts
            JOIN classic_session_claim sc ON sc.session_id = ts.id
            WHERE sc.status IN ('approved', 'revoked')
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            DROP TABLE classic_tournament_session_host_history
        ');
    }
}
