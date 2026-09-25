<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260925120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add optional description and url to common_venue';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE common_venue
                ADD description LONGTEXT DEFAULT NULL,
                ADD url VARCHAR(255) DEFAULT NULL
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE common_venue
                DROP COLUMN description,
                DROP COLUMN url
        ');
    }
}
