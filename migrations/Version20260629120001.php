<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260629120001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename Classic module tables with classic_ prefix';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('RENAME TABLE
            appeal TO classic_appeal,
            session_claim TO classic_session_claim,
            team TO classic_team,
            team_player TO classic_team_player,
            tournament TO classic_tournament,
            tournament_document TO classic_tournament_document,
            tournament_moderation_claim TO classic_tournament_moderation_claim,
            tournament_official TO classic_tournament_official,
            tournament_session TO classic_tournament_session,
            tournament_session_team TO classic_tournament_session_team,
            tournament_session_team_answer TO classic_tournament_session_team_answer,
            tournament_session_team_player TO classic_tournament_session_team_player
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('RENAME TABLE
            classic_appeal TO appeal,
            classic_session_claim TO session_claim,
            classic_team TO team,
            classic_team_player TO team_player,
            classic_tournament TO tournament,
            classic_tournament_document TO tournament_document,
            classic_tournament_moderation_claim TO tournament_moderation_claim,
            classic_tournament_official TO tournament_official,
            classic_tournament_session TO tournament_session,
            classic_tournament_session_team TO tournament_session_team,
            classic_tournament_session_team_answer TO tournament_session_team_answer,
            classic_tournament_session_team_player TO tournament_session_team_player
        ');
    }
}
