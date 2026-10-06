<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261006073709 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Tick a workout set once it is done.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workout_set ADD is_complete TINYINT DEFAULT 0 NOT NULL');
        // A finished workout holds only done sets: the ones already there were done.
        $this->addSql('UPDATE workout_set ws INNER JOIN workout_exercise we ON we.id = ws.workout_exercise_id INNER JOIN workout_block wb ON wb.id = we.workout_block_id INNER JOIN workout w ON w.id = wb.workout_id SET ws.is_complete = 1 WHERE w.finished_at IS NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workout_set DROP is_complete');
    }
}
