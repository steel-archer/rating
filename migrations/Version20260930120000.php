<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260930120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add visibility (closed/open) to classic_tournament_document';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE classic_tournament_document
                ADD visibility VARCHAR(20) NOT NULL DEFAULT 'closed'
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE classic_tournament_document
                DROP visibility
        ');
    }
}
