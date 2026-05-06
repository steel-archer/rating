<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260502201021 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add created_by, is_approved, created_at to venue; add created_at to venue_representative; add unique constraint on venue name+town';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE venue
            ADD created_by_id INT DEFAULT NULL,
            ADD is_approved TINYINT(1) NOT NULL DEFAULT 0,
            ADD created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            ADD CONSTRAINT FK_91911B0EB03A8386
                FOREIGN KEY (created_by_id) REFERENCES `user` (id),
            ADD INDEX IDX_venue_created_by (created_by_id),
            ADD UNIQUE INDEX UQ_venue_name_town (name, town_id)
        ');
        $this->addSql('
            ALTER TABLE venue_representative
            ADD created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ');
        $this->addSql('UPDATE venue SET is_approved = 1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE venue_representative DROP created_at');
        $this->addSql('
            ALTER TABLE venue
            DROP FOREIGN KEY FK_91911B0EB03A8386,
            DROP INDEX IDX_venue_created_by,
            DROP INDEX UQ_venue_name_town,
            DROP created_by_id,
            DROP is_approved,
            DROP created_at
        ');
    }
}
