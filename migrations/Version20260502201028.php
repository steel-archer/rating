<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260502201028 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add unique constraint on town name + country';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE town
            ADD UNIQUE INDEX UQ_town_name_country (name, country_id)
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE town DROP INDEX UQ_town_name_country');
    }
}
