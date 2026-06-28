<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260628120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add discussion_link to tournament table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE tournament
            ADD COLUMN discussion_link VARCHAR(512) DEFAULT NULL
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE tournament
            DROP COLUMN discussion_link
        ');
    }
}
