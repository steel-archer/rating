<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260502201018 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add tournament status, createdBy and tournament_moderation_claim table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tournament ADD status VARCHAR(20) NOT NULL DEFAULT \'draft\'');
        $this->addSql('ALTER TABLE tournament ADD created_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tournament ADD CONSTRAINT FK_tournament_created_by FOREIGN KEY (created_by_id) REFERENCES `user` (id)');
        $this->addSql('CREATE INDEX IDX_tournament_created_by ON tournament (created_by_id)');

        $this->addSql('
            CREATE TABLE tournament_moderation_claim (
                id INT AUTO_INCREMENT NOT NULL,
                tournament_id INT NOT NULL,
                status VARCHAR(20) NOT NULL,
                comment LONGTEXT DEFAULT NULL,
                created_at DATETIME NOT NULL,
                resolved_at DATETIME DEFAULT NULL,
                UNIQUE INDEX UNIQ_tmc_tournament (tournament_id),
                CONSTRAINT FK_tmc_tournament FOREIGN KEY (tournament_id) REFERENCES tournament (id),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE tournament_moderation_claim');
        $this->addSql('ALTER TABLE tournament DROP FOREIGN KEY FK_tournament_created_by');
        $this->addSql('DROP INDEX IDX_tournament_created_by ON tournament');
        $this->addSql('ALTER TABLE tournament DROP status');
        $this->addSql('ALTER TABLE tournament DROP created_by_id');
    }
}
