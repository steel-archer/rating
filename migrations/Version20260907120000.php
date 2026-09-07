<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260907120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make host_id required on classic_tournament_session (fallback to representative)';
    }

    public function up(Schema $schema): void
    {
        // Backfill missing hosts with the session representative
        $this->addSql('
            UPDATE classic_tournament_session
            SET host_id = representative_id
            WHERE host_id IS NULL
        ');

        // Recreate FK without ON DELETE behaviour change, then enforce NOT NULL
        $this->addSql('
            ALTER TABLE classic_tournament_session
                DROP FOREIGN KEY FK_BB4B2EDE1FB8D185
        ');

        $this->addSql('
            ALTER TABLE classic_tournament_session
                CHANGE host_id host_id INT NOT NULL
        ');

        $this->addSql('
            ALTER TABLE classic_tournament_session
                ADD CONSTRAINT FK_BB4B2EDE1FB8D185
                    FOREIGN KEY (host_id) REFERENCES common_player (id)
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE classic_tournament_session
                DROP FOREIGN KEY FK_BB4B2EDE1FB8D185
        ');

        $this->addSql('
            ALTER TABLE classic_tournament_session
                CHANGE host_id host_id INT DEFAULT NULL
        ');

        $this->addSql('
            ALTER TABLE classic_tournament_session
                ADD CONSTRAINT FK_BB4B2EDE1FB8D185
                    FOREIGN KEY (host_id) REFERENCES common_player (id)
        ');
    }
}
