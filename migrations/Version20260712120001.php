<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260712120001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migrate questions_per_tour data into questions_per_tour_map and drop old column';
    }

    public function up(Schema $schema): void
    {
        $columns = $this->connection->fetchFirstColumn('SHOW COLUMNS FROM classic_tournament LIKE "questions_per_tour"');

        if ($columns === []) {
            // Column already removed (e.g. fresh DB created from final schema)
            return;
        }

        $rows = $this->connection->fetchAllAssociative('
            SELECT id, tours_count, questions_per_tour
            FROM classic_tournament
            WHERE questions_per_tour IS NOT NULL
              AND tours_count IS NOT NULL
        ');

        foreach ($rows as $row) {
            $map = array_fill(0, (int) $row['tours_count'], (int) $row['questions_per_tour']);
            $this->addSql(
                'UPDATE classic_tournament SET questions_per_tour_map = ? WHERE id = ?',
                [json_encode($map, JSON_THROW_ON_ERROR), $row['id']],
            );
        }

        $this->addSql('
            ALTER TABLE classic_tournament
                DROP COLUMN questions_per_tour
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE classic_tournament
                ADD questions_per_tour INT DEFAULT NULL
        ');

        $this->addSql('
            UPDATE classic_tournament
            SET questions_per_tour = JSON_EXTRACT(questions_per_tour_map, "$[0]")
            WHERE questions_per_tour_map IS NOT NULL
        ');
    }
}
