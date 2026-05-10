<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260502201033 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add one_time_name column to tournament_session_team table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE tournament_session_team
                ADD one_time_name VARCHAR(255) DEFAULT NULL
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE tournament_session_team
                DROP COLUMN one_time_name
        ');
    }
}
