<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260502201002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create town table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE town (
                id INT AUTO_INCREMENT NOT NULL,
                name VARCHAR(255) NOT NULL,
                country_id INT NOT NULL,
                INDEX IDX_town_country (country_id),
                PRIMARY KEY (id),
                CONSTRAINT FK_4CE6C7A4F92F3E70 FOREIGN KEY (country_id) REFERENCES country (id)
            ) DEFAULT CHARACTER SET utf8mb4
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE town');
    }
}
