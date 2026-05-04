<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260502201016 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed Ukraine and seasons';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            INSERT INTO country (id, name) VALUES (1, 'Україна')
            ON DUPLICATE KEY UPDATE name = VALUES(name)
        ");
        $this->addSql("
            INSERT INTO season (id, name, started_at, ended_at) VALUES
                (1, '2024-2025', '2024-10-01 00:00:00', '2025-09-30 23:59:59'),
                (2, '2025-2026', '2025-10-01 00:00:00', '2026-09-30 23:59:59')
            ON DUPLICATE KEY UPDATE name = VALUES(name), started_at = VALUES(started_at), ended_at = VALUES(ended_at)
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM season WHERE id IN (1, 2)');
        $this->addSql('DELETE FROM country WHERE id = 1');
    }
}
