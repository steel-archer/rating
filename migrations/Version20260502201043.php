<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260502201043 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add blocked_reason to user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE `user`
            ADD COLUMN blocked_reason VARCHAR(500) DEFAULT NULL
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE `user`
            DROP COLUMN blocked_reason
        ');
    }
}
