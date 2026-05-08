<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260502201027 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add town namesake: Mykolaiv (Lviv oblast)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            INSERT INTO town (name, country_id)
            SELECT 'Миколаїв (Львівська обл.)', id
            FROM country
            WHERE name = 'Україна'
            LIMIT 1
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM town WHERE name = 'Миколаїв (Львівська обл.)'");
    }
}
