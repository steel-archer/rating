<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260502201032 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add contact fields to user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE `user`
                ADD telegram VARCHAR(32) DEFAULT NULL,
                ADD facebook VARCHAR(50) DEFAULT NULL,
                ADD phone VARCHAR(20) DEFAULT NULL
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE `user`
                DROP COLUMN telegram,
                DROP COLUMN facebook,
                DROP COLUMN phone
        ');
    }
}
