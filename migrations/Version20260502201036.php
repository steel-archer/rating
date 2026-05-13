<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260502201036 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add results_hidden_until to tournament';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE tournament
                ADD results_hidden_until DATETIME DEFAULT NULL
                    COMMENT \'(DC2Type:datetime_immutable)\'
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE tournament
                DROP COLUMN results_hidden_until
        ');
    }
}
