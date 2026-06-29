<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260629120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename Common module tables with common_ prefix';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('RENAME TABLE
            country TO common_country,
            player TO common_player,
            player_claim TO common_player_claim,
            season TO common_season,
            town TO common_town,
            `user` TO common_user,
            venue TO common_venue,
            venue_representative TO common_venue_representative
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('RENAME TABLE
            common_country TO country,
            common_player TO player,
            common_player_claim TO player_claim,
            common_season TO season,
            common_town TO town,
            common_user TO `user`,
            common_venue TO venue,
            common_venue_representative TO venue_representative
        ');
    }
}
