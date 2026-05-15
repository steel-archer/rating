<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260502201037 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add dispute fields to tournament_session_team_answer';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE tournament_session_team_answer
                ADD dispute_text VARCHAR(500) DEFAULT NULL,
                ADD dispute_status VARCHAR(20) DEFAULT NULL,
                ADD dispute_comment VARCHAR(500) DEFAULT NULL
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE tournament_session_team_answer
                DROP COLUMN dispute_text,
                DROP COLUMN dispute_status,
                DROP COLUMN dispute_comment
        ');
    }
}
