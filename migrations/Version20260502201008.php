<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260502201008 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create venue table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE venue (
                id INT AUTO_INCREMENT NOT NULL,
                name VARCHAR(255) NOT NULL,
                town_id INT NOT NULL,
                INDEX IDX_venue_town (town_id),
                PRIMARY KEY (id),
                CONSTRAINT FK_venue_town FOREIGN KEY (town_id) REFERENCES town (id)
            ) DEFAULT CHARACTER SET utf8mb4
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE venue');
    }
}
