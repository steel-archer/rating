<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260502201019 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add updated_at to tournament';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tournament ADD updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tournament DROP updated_at');
    }
}
