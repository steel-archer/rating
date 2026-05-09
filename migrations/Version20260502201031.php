<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260502201031 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create tournament_document table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE tournament_document (
                id INT AUTO_INCREMENT NOT NULL,
                tournament_id INT NOT NULL,
                original_name VARCHAR(255) NOT NULL,
                stored_name VARCHAR(255) NOT NULL,
                mime_type VARCHAR(100) NOT NULL,
                size INT NOT NULL,
                created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                INDEX IDX_td_tournament (tournament_id),
                PRIMARY KEY (id),
                CONSTRAINT FK_td_tournament
                    FOREIGN KEY (tournament_id) REFERENCES tournament (id)
            ) DEFAULT CHARACTER SET utf8mb4
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE tournament_document');
    }
}
