<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260502201007 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_captain to team_player';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE team_player ADD is_captain TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE team_player DROP COLUMN is_captain');
    }
}
