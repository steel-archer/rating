<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260502201040 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_question_removed to tournament_session_team_answer';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE tournament_session_team_answer
                ADD is_question_removed TINYINT(1) NOT NULL DEFAULT 0
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE tournament_session_team_answer
                DROP COLUMN is_question_removed
        ');
    }
}
