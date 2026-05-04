<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260502201012 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create tournament_session_team table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE tournament_session_team (
                id INT AUTO_INCREMENT NOT NULL,
                tournament_session_id INT NOT NULL,
                team_id INT NOT NULL,
                score INT DEFAULT NULL,
                INDEX IDX_tst_session (tournament_session_id),
                INDEX IDX_tst_team (team_id),
                UNIQUE INDEX UQ_session_team (tournament_session_id, team_id),
                PRIMARY KEY (id),
                CONSTRAINT FK_9413DA63B5F4C85 FOREIGN KEY (tournament_session_id) REFERENCES tournament_session (id),
                CONSTRAINT FK_9413DA63296CD8AE FOREIGN KEY (team_id) REFERENCES team (id)
            ) DEFAULT CHARACTER SET utf8mb4
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE tournament_session_team');
    }
}
