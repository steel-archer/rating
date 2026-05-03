<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260502201011 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create tournament_session table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE tournament_session (
                id INT AUTO_INCREMENT NOT NULL,
                tournament_id INT NOT NULL,
                venue_id INT NOT NULL,
                representative_id INT NOT NULL,
                host_id INT DEFAULT NULL,
                played_at DATETIME DEFAULT NULL,
                INDEX IDX_ts_tournament (tournament_id),
                INDEX IDX_ts_venue (venue_id),
                INDEX IDX_ts_representative (representative_id),
                INDEX IDX_ts_host (host_id),
                UNIQUE INDEX UQ_tournament_venue (tournament_id, venue_id),
                PRIMARY KEY (id),
                CONSTRAINT FK_ts_tournament FOREIGN KEY (tournament_id) REFERENCES tournament (id),
                CONSTRAINT FK_ts_venue FOREIGN KEY (venue_id) REFERENCES venue (id),
                CONSTRAINT FK_ts_representative FOREIGN KEY (representative_id) REFERENCES player (id),
                CONSTRAINT FK_ts_host FOREIGN KEY (host_id) REFERENCES player (id)
            ) DEFAULT CHARACTER SET utf8mb4
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE tournament_session');
    }
}
