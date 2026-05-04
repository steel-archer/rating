<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260502201014 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE `user` (
                id INT AUTO_INCREMENT NOT NULL,
                email VARCHAR(255) NOT NULL,
                google_id VARCHAR(255) NOT NULL,
                roles JSON NOT NULL,
                player_id INT DEFAULT NULL,
                UNIQUE INDEX UNIQ_user_email (email),
                UNIQUE INDEX UNIQ_user_google_id (google_id),
                UNIQUE INDEX UNIQ_user_player (player_id),
                PRIMARY KEY (id),
                CONSTRAINT FK_8D93D64999E6F5DF FOREIGN KEY (player_id) REFERENCES player (id)
            ) DEFAULT CHARACTER SET utf8mb4
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE `user`');
    }
}
