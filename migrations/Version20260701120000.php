<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260701120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add format to classic_tournament';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE classic_tournament
                ADD format VARCHAR(20) NOT NULL DEFAULT 'distributed'
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE classic_tournament
                DROP COLUMN format
        ');
    }
}
