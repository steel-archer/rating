<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260502201044 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add terms_accepted_at to user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE `user`
            ADD COLUMN terms_accepted_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE `user`
            DROP COLUMN terms_accepted_at
        ');
    }
}
