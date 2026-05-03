<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260502201013 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add started_at and ended_at to season table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE season
                ADD started_at DATETIME DEFAULT NULL,
                ADD ended_at DATETIME DEFAULT NULL
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE season
                DROP started_at,
                DROP ended_at
        ');
    }
}
