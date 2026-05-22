<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260502201042 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Normalize tournament date fields to 00:00:00';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            UPDATE tournament
            SET
                started_at = DATE(started_at),
                ended_at = DATE(ended_at),
                results_hidden_until = DATE(results_hidden_until),
                registration_deadline = DATE(registration_deadline),
                details_hidden_until = DATE(details_hidden_until),
                submission_deadline = DATE(submission_deadline),
                appeal_deadline = DATE(appeal_deadline)
        ');
    }

    public function down(Schema $schema): void
    {
        // Irreversible: original time components are lost
    }
}
