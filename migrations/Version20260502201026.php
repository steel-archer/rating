<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260502201026 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add town_name to player_claim for user-suggested towns';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE player_claim
            ADD town_name VARCHAR(255) DEFAULT NULL
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player_claim DROP town_name');
    }
}
