<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260628120002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename Sievierodonetsk to Siverskodonetsk';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            UPDATE town
            SET name = 'Сіверськодонецьк'
            WHERE name = 'Сєвєродонецьк'
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            UPDATE town
            SET name = 'Сєвєродонецьк'
            WHERE name = 'Сіверськодонецьк'
        ");
    }
}
