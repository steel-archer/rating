<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260629120004 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_online to common_venue, create pseudo-town Online';
    }

    public function up(Schema $schema): void
    {
        // Add unique constraint on country name
        $this->addSql('
            ALTER TABLE common_country
                ADD UNIQUE INDEX UQ_country_name (name)
        ');

        // Ensure Ukraine exists in countries
        $this->addSql("
            INSERT IGNORE INTO common_country (name)
            VALUES ('Україна')
        ");

        // Create pseudo-town "Онлайн" linked to Ukraine
        $this->addSql("
            INSERT INTO common_town (name, country_id)
            SELECT 'Онлайн', id
            FROM common_country
            WHERE name = 'Україна'
            LIMIT 1
        ");

        // Add is_online column to venues
        $this->addSql('
            ALTER TABLE common_venue
                ADD is_online TINYINT NOT NULL DEFAULT 0
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE common_venue
                DROP COLUMN is_online
        ');

        $this->addSql("
            DELETE FROM common_town
            WHERE name = 'Онлайн'
        ");

        $this->addSql('
            ALTER TABLE common_country
                DROP INDEX UQ_country_name
        ');
    }
}
