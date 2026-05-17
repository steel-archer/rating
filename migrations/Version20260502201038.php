<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260502201038 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add registration_deadline, details_hidden_until, submission_deadline to tournament';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE tournament
                ADD registration_deadline DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                ADD details_hidden_until DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                ADD submission_deadline DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE tournament
                DROP COLUMN registration_deadline,
                DROP COLUMN details_hidden_until,
                DROP COLUMN submission_deadline
        ');
    }
}
