<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260701120001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add online_mode to classic_tournament';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE classic_tournament
                ADD online_mode VARCHAR(20) NOT NULL DEFAULT 'mixed'
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE classic_tournament
                DROP COLUMN online_mode
        ');
    }
}
