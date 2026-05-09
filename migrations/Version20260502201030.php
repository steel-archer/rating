<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260502201030 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create tournament_session_team_answer table and make score NOT NULL with default 0';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE tournament_session_team_answer (
                id INT AUTO_INCREMENT NOT NULL,
                tournament_session_team_id INT NOT NULL,
                question_number INT NOT NULL,
                is_correct TINYINT(1) NOT NULL,
                INDEX IDX_tsta_session_team (tournament_session_team_id),
                UNIQUE INDEX UQ_session_team_question (tournament_session_team_id, question_number),
                PRIMARY KEY (id),
                CONSTRAINT FK_tsta_session_team
                    FOREIGN KEY (tournament_session_team_id) REFERENCES tournament_session_team (id)
            ) DEFAULT CHARACTER SET utf8mb4
        ');

        $this->addSql('
            ALTER TABLE tournament_session_team
                MODIFY score INT NOT NULL DEFAULT 0
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE tournament_session_team
                MODIFY score INT DEFAULT NULL
        ');

        $this->addSql('DROP TABLE tournament_session_team_answer');
    }
}
