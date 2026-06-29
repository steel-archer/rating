<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260629120003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Sync schema: remove DC2Type comments (DBAL 4), fix FK cascade, normalize columns';
    }

    public function up(Schema $schema): void
    {
        // classic_tournament: remove DC2Type comments, drop DEFAULT on status
        $this->addSql('
            ALTER TABLE classic_tournament
                CHANGE status status VARCHAR(20) NOT NULL,
                CHANGE started_at started_at DATETIME DEFAULT NULL,
                CHANGE ended_at ended_at DATETIME DEFAULT NULL,
                CHANGE created_at created_at DATETIME NOT NULL,
                CHANGE updated_at updated_at DATETIME NOT NULL,
                CHANGE results_hidden_until results_hidden_until DATETIME DEFAULT NULL,
                CHANGE registration_deadline registration_deadline DATETIME DEFAULT NULL,
                CHANGE details_hidden_until details_hidden_until DATETIME DEFAULT NULL,
                CHANGE submission_deadline submission_deadline DATETIME DEFAULT NULL,
                CHANGE appeal_deadline appeal_deadline DATETIME DEFAULT NULL
        ');

        // classic_tournament_session
        $this->addSql('
            ALTER TABLE classic_tournament_session
                CHANGE played_at played_at DATETIME DEFAULT NULL,
                CHANGE created_at created_at DATETIME NOT NULL,
                CHANGE updated_at updated_at DATETIME NOT NULL
        ');

        // classic_tournament_document
        $this->addSql('
            ALTER TABLE classic_tournament_document
                CHANGE created_at created_at DATETIME NOT NULL
        ');

        // classic_session_claim: drop CASCADE, remove comments
        $this->addSql('
            ALTER TABLE classic_session_claim
                DROP FOREIGN KEY FK_B829D7D5613FECDF
        ');
        $this->addSql('
            ALTER TABLE classic_session_claim
                ADD CONSTRAINT FK_B829D7D5613FECDF
                    FOREIGN KEY (session_id) REFERENCES classic_tournament_session (id),
                CHANGE created_at created_at DATETIME NOT NULL,
                CHANGE resolved_at resolved_at DATETIME DEFAULT NULL
        ');

        // classic_tournament_moderation_claim
        $this->addSql('
            ALTER TABLE classic_tournament_moderation_claim
                CHANGE created_at created_at DATETIME NOT NULL,
                CHANGE resolved_at resolved_at DATETIME DEFAULT NULL
        ');

        // classic_appeal
        $this->addSql('
            ALTER TABLE classic_appeal
                CHANGE created_at created_at DATETIME NOT NULL
        ');

        // common_player
        $this->addSql('
            ALTER TABLE common_player
                CHANGE created_at created_at DATETIME NOT NULL,
                CHANGE updated_at updated_at DATETIME NOT NULL
        ');

        // common_user
        $this->addSql('
            ALTER TABLE common_user
                CHANGE created_at created_at DATETIME NOT NULL,
                CHANGE updated_at updated_at DATETIME NOT NULL,
                CHANGE terms_accepted_at terms_accepted_at DATETIME DEFAULT NULL
        ');

        // common_venue
        $this->addSql('
            ALTER TABLE common_venue
                CHANGE created_at created_at DATETIME NOT NULL,
                CHANGE is_approved is_approved TINYINT NOT NULL
        ');

        // common_venue_representative
        $this->addSql('
            ALTER TABLE common_venue_representative
                CHANGE created_at created_at DATETIME NOT NULL
        ');

        // common_season
        $this->addSql('
            ALTER TABLE common_season
                CHANGE started_at started_at DATETIME DEFAULT NULL,
                CHANGE ended_at ended_at DATETIME DEFAULT NULL
        ');

        // classic_tournament_session_team
        $this->addSql('
            ALTER TABLE classic_tournament_session_team
                CHANGE score score INT NOT NULL,
                CHANGE results_submitted results_submitted TINYINT NOT NULL
        ');

        // classic_tournament_session_team_answer
        $this->addSql('
            ALTER TABLE classic_tournament_session_team_answer
                CHANGE is_question_removed is_question_removed TINYINT NOT NULL
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE classic_tournament
                CHANGE status status VARCHAR(20) NOT NULL DEFAULT 'draft',
                CHANGE started_at started_at DATETIME DEFAULT NULL
                    COMMENT '(DC2Type:datetime_immutable)',
                CHANGE ended_at ended_at DATETIME DEFAULT NULL
                    COMMENT '(DC2Type:datetime_immutable)',
                CHANGE created_at created_at DATETIME NOT NULL
                    COMMENT '(DC2Type:datetime_immutable)',
                CHANGE updated_at updated_at DATETIME NOT NULL
                    COMMENT '(DC2Type:datetime_immutable)',
                CHANGE results_hidden_until results_hidden_until DATETIME DEFAULT NULL
                    COMMENT '(DC2Type:datetime_immutable)',
                CHANGE registration_deadline registration_deadline DATETIME DEFAULT NULL
                    COMMENT '(DC2Type:datetime_immutable)',
                CHANGE details_hidden_until details_hidden_until DATETIME DEFAULT NULL
                    COMMENT '(DC2Type:datetime_immutable)',
                CHANGE submission_deadline submission_deadline DATETIME DEFAULT NULL
                    COMMENT '(DC2Type:datetime_immutable)',
                CHANGE appeal_deadline appeal_deadline DATETIME DEFAULT NULL
                    COMMENT '(DC2Type:datetime_immutable)'
        ");

        $this->addSql("
            ALTER TABLE classic_tournament_session
                CHANGE played_at played_at DATETIME DEFAULT NULL
                    COMMENT '(DC2Type:datetime_immutable)',
                CHANGE created_at created_at DATETIME NOT NULL
                    COMMENT '(DC2Type:datetime_immutable)',
                CHANGE updated_at updated_at DATETIME NOT NULL
                    COMMENT '(DC2Type:datetime_immutable)'
        ");

        $this->addSql("
            ALTER TABLE classic_tournament_document
                CHANGE created_at created_at DATETIME NOT NULL
                    COMMENT '(DC2Type:datetime_immutable)'
        ");

        $this->addSql('
            ALTER TABLE classic_session_claim
                DROP FOREIGN KEY FK_B829D7D5613FECDF
        ');
        $this->addSql("
            ALTER TABLE classic_session_claim
                ADD CONSTRAINT FK_B829D7D5613FECDF
                    FOREIGN KEY (session_id) REFERENCES classic_tournament_session (id)
                    ON DELETE CASCADE,
                CHANGE created_at created_at DATETIME NOT NULL
                    COMMENT '(DC2Type:datetime_immutable)',
                CHANGE resolved_at resolved_at DATETIME DEFAULT NULL
                    COMMENT '(DC2Type:datetime_immutable)'
        ");

        $this->addSql("
            ALTER TABLE classic_tournament_moderation_claim
                CHANGE created_at created_at DATETIME NOT NULL
                    COMMENT '(DC2Type:datetime_immutable)',
                CHANGE resolved_at resolved_at DATETIME DEFAULT NULL
                    COMMENT '(DC2Type:datetime_immutable)'
        ");

        $this->addSql("
            ALTER TABLE classic_appeal
                CHANGE created_at created_at DATETIME NOT NULL
                    COMMENT '(DC2Type:datetime_immutable)'
        ");

        $this->addSql("
            ALTER TABLE common_player
                CHANGE created_at created_at DATETIME NOT NULL
                    COMMENT '(DC2Type:datetime_immutable)',
                CHANGE updated_at updated_at DATETIME NOT NULL
                    COMMENT '(DC2Type:datetime_immutable)'
        ");

        $this->addSql("
            ALTER TABLE common_user
                CHANGE created_at created_at DATETIME NOT NULL
                    COMMENT '(DC2Type:datetime_immutable)',
                CHANGE updated_at updated_at DATETIME NOT NULL
                    COMMENT '(DC2Type:datetime_immutable)',
                CHANGE terms_accepted_at terms_accepted_at DATETIME DEFAULT NULL
                    COMMENT '(DC2Type:datetime_immutable)'
        ");

        $this->addSql("
            ALTER TABLE common_venue
                CHANGE created_at created_at DATETIME NOT NULL
                    COMMENT '(DC2Type:datetime_immutable)',
                CHANGE is_approved is_approved TINYINT(1) NOT NULL DEFAULT 0
        ");

        $this->addSql("
            ALTER TABLE common_venue_representative
                CHANGE created_at created_at DATETIME NOT NULL
                    COMMENT '(DC2Type:datetime_immutable)'
        ");

        $this->addSql("
            ALTER TABLE common_season
                CHANGE started_at started_at DATETIME DEFAULT NULL
                    COMMENT '(DC2Type:datetime_immutable)',
                CHANGE ended_at ended_at DATETIME DEFAULT NULL
                    COMMENT '(DC2Type:datetime_immutable)'
        ");

        $this->addSql('
            ALTER TABLE classic_tournament_session_team
                CHANGE score score INT NOT NULL DEFAULT 0,
                CHANGE results_submitted results_submitted TINYINT(1) NOT NULL DEFAULT 0
        ');

        $this->addSql('
            ALTER TABLE classic_tournament_session_team_answer
                CHANGE is_question_removed is_question_removed TINYINT(1) NOT NULL DEFAULT 0
        ');
    }
}
