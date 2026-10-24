<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261024100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Every done set counts for the personal bests, and a unilateral set\'s volume is no longer doubled: the summaries\' volume is worked out again.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE set_type DROP counts_for_personal_bests');
        $this->addSql(<<<'SQL'
            UPDATE workout_summary summary
            SET summary.volume_in_kilograms = (
                SELECT ROUND(SUM(workout_set.reps * workout_set.weight_in_kilograms), 2)
                FROM workout_set
                INNER JOIN workout_exercise ON workout_exercise.id = workout_set.workout_exercise_id
                INNER JOIN workout_block ON workout_block.id = workout_exercise.workout_block_id
                WHERE workout_block.workout_id = summary.workout_id
                  AND workout_set.is_complete = 1
                  AND workout_set.reps IS NOT NULL
                  AND workout_set.weight_in_kilograms IS NOT NULL
            )
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE set_type ADD counts_for_personal_bests TINYINT DEFAULT 1 NOT NULL');
    }
}
