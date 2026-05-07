<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260502201022 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add created_at, updated_at to player and user';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE player
            ADD created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            ADD updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ');
        $this->addSql('
            ALTER TABLE `user`
            ADD created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            ADD updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player DROP created_at, DROP updated_at');
        $this->addSql('ALTER TABLE `user` DROP created_at, DROP updated_at');
    }
}
