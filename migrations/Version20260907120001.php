<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260907120001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create classic_tournament_document_download audit table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE classic_tournament_document_download (
                id INT AUTO_INCREMENT NOT NULL,
                document_id INT NOT NULL,
                player_id INT NOT NULL,
                downloaded_at DATETIME NOT NULL,
                INDEX IDX_tdd_document (document_id),
                INDEX IDX_tdd_player (player_id),
                PRIMARY KEY (id),
                CONSTRAINT FK_tdd_document
                    FOREIGN KEY (document_id) REFERENCES classic_tournament_document (id),
                CONSTRAINT FK_tdd_player
                    FOREIGN KEY (player_id) REFERENCES common_player (id)
            ) DEFAULT CHARACTER SET utf8mb4
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            DROP TABLE classic_tournament_document_download
        ');
    }
}
