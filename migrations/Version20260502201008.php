<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260502201008 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create venue_representative table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE venue_representative (
                id INT AUTO_INCREMENT NOT NULL,
                venue_id INT NOT NULL,
                player_id INT NOT NULL,
                INDEX IDX_vr_venue (venue_id),
                INDEX IDX_vr_player (player_id),
                UNIQUE INDEX UQ_venue_player (venue_id, player_id),
                PRIMARY KEY (id),
                CONSTRAINT FK_127B853740A73EBA FOREIGN KEY (venue_id) REFERENCES venue (id),
                CONSTRAINT FK_127B853799E6F5DF FOREIGN KEY (player_id) REFERENCES player (id)
            ) DEFAULT CHARACTER SET utf8mb4
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE venue_representative');
    }
}
