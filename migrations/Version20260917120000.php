<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260917120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add country selection to player_claim for user-suggested towns';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE common_player_claim
            ADD country_id INT DEFAULT NULL,
            ADD INDEX IDX_player_claim_country (country_id),
            ADD CONSTRAINT FK_player_claim_country
                FOREIGN KEY (country_id) REFERENCES common_country (id)
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE common_player_claim
            DROP FOREIGN KEY FK_player_claim_country,
            DROP INDEX IDX_player_claim_country,
            DROP country_id
        ');
    }
}
