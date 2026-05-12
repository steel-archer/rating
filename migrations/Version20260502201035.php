<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260502201035 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add results_submitted column to tournament_session_team table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE tournament_session_team
                ADD results_submitted TINYINT(1) NOT NULL DEFAULT 0
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE tournament_session_team
                DROP COLUMN results_submitted
        ');
    }
}
