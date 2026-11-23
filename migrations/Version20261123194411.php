<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261123194411 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Training planning entries: a routine planned on a rule of dates.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE training_planning_entry (id INT AUTO_INCREMENT NOT NULL, rule VARCHAR(32) NOT NULL, start_date DATE NOT NULL, end_date DATE DEFAULT NULL, weekdays JSON NOT NULL, week_interval INT NOT NULL, month_weeks JSON NOT NULL, month_days JSON NOT NULL, start_time_in_minutes INT DEFAULT NULL, duration_in_minutes INT DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, owner_id INT NOT NULL, routine_id INT NOT NULL, INDEX training_planning_entry_owner (owner_id), INDEX IDX_3DFB2D52F27A94C7 (routine_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE training_planning_entry ADD CONSTRAINT FK_3DFB2D527E3C61F9 FOREIGN KEY (owner_id) REFERENCES user_account (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE training_planning_entry ADD CONSTRAINT FK_3DFB2D52F27A94C7 FOREIGN KEY (routine_id) REFERENCES routine (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE training_planning_entry DROP FOREIGN KEY FK_3DFB2D527E3C61F9');
        $this->addSql('ALTER TABLE training_planning_entry DROP FOREIGN KEY FK_3DFB2D52F27A94C7');
        $this->addSql('DROP TABLE training_planning_entry');
    }
}
