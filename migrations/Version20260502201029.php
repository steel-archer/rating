<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260502201029 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change created_by_id FK from user to player in tournament and venue tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE tournament
                DROP FOREIGN KEY FK_tournament_created_by
        ');

        $this->addSql('
            ALTER TABLE venue
                DROP FOREIGN KEY FK_91911B0EB03A8386
        ');

        $this->addSql('
            UPDATE tournament t
                JOIN `user` u ON t.created_by_id = u.id
            SET t.created_by_id = u.player_id
        ');

        $this->addSql('
            UPDATE venue v
                JOIN `user` u ON v.created_by_id = u.id
            SET v.created_by_id = u.player_id
        ');

        $this->addSql('
            ALTER TABLE tournament
                ADD CONSTRAINT FK_tournament_created_by
                    FOREIGN KEY (created_by_id) REFERENCES player (id)
        ');

        $this->addSql('
            ALTER TABLE venue
                ADD CONSTRAINT FK_91911B0EB03A8386
                    FOREIGN KEY (created_by_id) REFERENCES player (id)
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE tournament
                DROP FOREIGN KEY FK_tournament_created_by
        ');

        $this->addSql('
            ALTER TABLE venue
                DROP FOREIGN KEY FK_91911B0EB03A8386
        ');

        $this->addSql('
            UPDATE tournament t
                JOIN player p ON t.created_by_id = p.id
                JOIN `user` u ON u.player_id = p.id
            SET t.created_by_id = u.id
        ');

        $this->addSql('
            UPDATE venue v
                JOIN player p ON v.created_by_id = p.id
                JOIN `user` u ON u.player_id = p.id
            SET v.created_by_id = u.id
        ');

        $this->addSql('
            ALTER TABLE tournament
                ADD CONSTRAINT FK_tournament_created_by
                    FOREIGN KEY (created_by_id) REFERENCES `user` (id)
        ');

        $this->addSql('
            ALTER TABLE venue
                ADD CONSTRAINT FK_91911B0EB03A8386
                    FOREIGN KEY (created_by_id) REFERENCES `user` (id)
        ');
    }
}
