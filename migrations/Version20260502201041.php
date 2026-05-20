<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260502201041 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create appeal table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE appeal (
                id INT AUTO_INCREMENT NOT NULL,
                tournament_session_team_answer_id INT NOT NULL,
                type VARCHAR(20) NOT NULL,
                text LONGTEXT NOT NULL,
                status VARCHAR(20) NOT NULL,
                verdict LONGTEXT DEFAULT NULL,
                created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                UNIQUE INDEX UQ_appeal_answer (tournament_session_team_answer_id),
                INDEX IDX_appeal_answer (tournament_session_team_answer_id),
                CONSTRAINT FK_appeal_answer
                    FOREIGN KEY (tournament_session_team_answer_id)
                    REFERENCES tournament_session_team_answer (id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE appeal');
    }
}
