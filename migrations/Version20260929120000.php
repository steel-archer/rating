<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260929120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add optional announcement_url to classic_tournament_session';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE classic_tournament_session
                ADD announcement_url VARCHAR(255) DEFAULT NULL
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE classic_tournament_session
                DROP COLUMN announcement_url
        ');
    }
}
