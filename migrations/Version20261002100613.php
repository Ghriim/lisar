<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261002100613 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the workouts: workouts, their blocks, exercises and sets.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE workout (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(128) DEFAULT NULL, note LONGTEXT DEFAULT NULL, feeling SMALLINT DEFAULT NULL, started_at DATETIME NOT NULL, finished_at DATETIME DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, owner_id INT NOT NULL, INDEX workout_owner_started (owner_id, started_at), INDEX IDX_649FFB727E3C61F9 (owner_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE workout_block (id INT AUTO_INCREMENT NOT NULL, position INT NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, workout_id INT NOT NULL, INDEX IDX_DAD02436A6CCCFC9 (workout_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE workout_exercise (id INT AUTO_INCREMENT NOT NULL, position INT NOT NULL, note LONGTEXT DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, workout_block_id INT NOT NULL, movement_id INT NOT NULL, INDEX IDX_76AB38AA319C6FBA (workout_block_id), INDEX IDX_76AB38AA229E70A7 (movement_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE workout_set (id INT AUTO_INCREMENT NOT NULL, position INT NOT NULL, reps INT DEFAULT NULL, weight_in_kilograms NUMERIC(6, 2) DEFAULT NULL, duration_in_seconds INT DEFAULT NULL, distance_in_metres INT DEFAULT NULL, rpe NUMERIC(3, 1) DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, workout_exercise_id INT NOT NULL, set_type_id INT DEFAULT NULL, INDEX IDX_6FDEFB94E435DB6B (workout_exercise_id), INDEX IDX_6FDEFB9442E06A74 (set_type_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE workout ADD CONSTRAINT FK_649FFB727E3C61F9 FOREIGN KEY (owner_id) REFERENCES user_account (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE workout_block ADD CONSTRAINT FK_DAD02436A6CCCFC9 FOREIGN KEY (workout_id) REFERENCES workout (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE workout_exercise ADD CONSTRAINT FK_76AB38AA319C6FBA FOREIGN KEY (workout_block_id) REFERENCES workout_block (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE workout_exercise ADD CONSTRAINT FK_76AB38AA229E70A7 FOREIGN KEY (movement_id) REFERENCES movement (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE workout_set ADD CONSTRAINT FK_6FDEFB94E435DB6B FOREIGN KEY (workout_exercise_id) REFERENCES workout_exercise (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE workout_set ADD CONSTRAINT FK_6FDEFB9442E06A74 FOREIGN KEY (set_type_id) REFERENCES set_type (id) ON DELETE RESTRICT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workout DROP FOREIGN KEY FK_649FFB727E3C61F9');
        $this->addSql('ALTER TABLE workout_block DROP FOREIGN KEY FK_DAD02436A6CCCFC9');
        $this->addSql('ALTER TABLE workout_exercise DROP FOREIGN KEY FK_76AB38AA319C6FBA');
        $this->addSql('ALTER TABLE workout_exercise DROP FOREIGN KEY FK_76AB38AA229E70A7');
        $this->addSql('ALTER TABLE workout_set DROP FOREIGN KEY FK_6FDEFB94E435DB6B');
        $this->addSql('ALTER TABLE workout_set DROP FOREIGN KEY FK_6FDEFB9442E06A74');
        $this->addSql('DROP TABLE workout');
        $this->addSql('DROP TABLE workout_block');
        $this->addSql('DROP TABLE workout_exercise');
        $this->addSql('DROP TABLE workout_set');
    }
}
