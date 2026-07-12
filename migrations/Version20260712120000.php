<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260712120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add questions_per_tour_map column to classic_tournament';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE classic_tournament
                ADD questions_per_tour_map JSON DEFAULT NULL
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE classic_tournament
                DROP COLUMN questions_per_tour_map
        ');
    }
}
