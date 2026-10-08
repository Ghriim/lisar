<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261008133849 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'The summary of each workout, kept rather than worked out at each read.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE workout_summary (id INT AUTO_INCREMENT NOT NULL, set_count INT NOT NULL, volume_in_kilograms NUMERIC(12, 2) DEFAULT NULL, duration_in_seconds INT DEFAULT NULL, distance_in_metres INT DEFAULT NULL, personal_best_count INT NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, workout_id INT NOT NULL, UNIQUE INDEX UNIQ_B2EE0ACBA6CCCFC9 (workout_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE workout_summary ADD CONSTRAINT FK_B2EE0ACBA6CCCFC9 FOREIGN KEY (workout_id) REFERENCES workout (id) ON DELETE CASCADE');
        // Every workout already there gets its own, added up from its done sets as the handlers
        // would, and its records counted.
        $this->addSql(<<<'SQL'
            INSERT INTO workout_summary (workout_id, set_count, volume_in_kilograms, duration_in_seconds, distance_in_metres, personal_best_count, created_at, updated_at)
            SELECT
                workout.id,
                COUNT(workout_set.id),
                ROUND(SUM(CASE WHEN workout_set.reps IS NOT NULL AND workout_set.weight_in_kilograms IS NOT NULL
                    THEN workout_set.reps * workout_set.weight_in_kilograms * IF(movement.is_unilateral = 1, 2, 1) END), 2),
                SUM(workout_set.duration_in_seconds),
                SUM(workout_set.distance_in_metres),
                (SELECT COUNT(*) FROM personal_best WHERE personal_best.workout_id = workout.id),
                NOW(),
                NOW()
            FROM workout
            LEFT JOIN workout_block ON workout_block.workout_id = workout.id
            LEFT JOIN workout_exercise ON workout_exercise.workout_block_id = workout_block.id
            LEFT JOIN movement ON movement.id = workout_exercise.movement_id
            LEFT JOIN workout_set ON workout_set.workout_exercise_id = workout_exercise.id AND workout_set.is_complete = 1
            GROUP BY workout.id
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workout_summary DROP FOREIGN KEY FK_B2EE0ACBA6CCCFC9');
        $this->addSql('DROP TABLE workout_summary');
    }
}
