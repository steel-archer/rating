<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260629120005 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_online to classic_tournament_session';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE classic_tournament_session
                ADD is_online TINYINT NOT NULL DEFAULT 0
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE classic_tournament_session
                DROP COLUMN is_online
        ');
    }
}
