<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260628120003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Set created_by_id for venues that have representatives but no creator';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            UPDATE venue v
            JOIN (
                SELECT vr.venue_id,
                       vr.player_id
                FROM venue_representative vr
                INNER JOIN (
                    SELECT venue_id, MIN(id) AS min_id
                    FROM venue_representative
                    GROUP BY venue_id
                ) first_rep ON vr.id = first_rep.min_id
            ) sub ON v.id = sub.venue_id
            SET v.created_by_id = sub.player_id
            WHERE v.created_by_id IS NULL
        ");
    }

    public function down(Schema $schema): void
    {
        // No reliable way to distinguish original nulls from migrated ones
    }
}
