<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261124152530 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Task planning entries: a task model on a rule of dates, and the tasks they make linked back to their entry and date.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE task_planning_entry (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, rule VARCHAR(32) NOT NULL, start_date DATE NOT NULL, end_date DATE DEFAULT NULL, weekdays JSON NOT NULL, week_interval INT NOT NULL, month_weeks JSON NOT NULL, month_days JSON NOT NULL, start_time_in_minutes INT DEFAULT NULL, duration_in_minutes INT DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, owner_id INT NOT NULL, category_id INT DEFAULT NULL, INDEX task_planning_entry_owner (owner_id), INDEX IDX_A6A4514012469DE2 (category_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE task_planning_entry ADD CONSTRAINT FK_A6A451407E3C61F9 FOREIGN KEY (owner_id) REFERENCES user_account (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE task_planning_entry ADD CONSTRAINT FK_A6A4514012469DE2 FOREIGN KEY (category_id) REFERENCES task_category (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE task ADD planned_date DATE DEFAULT NULL, ADD planning_entry_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB258D331FEF FOREIGN KEY (planning_entry_id) REFERENCES task_planning_entry (id) ON DELETE SET NULL');
        $this->addSql('CREATE UNIQUE INDEX task_planning_entry_date ON task (planning_entry_id, planned_date)');
        $this->addSql('CREATE INDEX IDX_527EDB258D331FEF ON task (planning_entry_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE task_planning_entry DROP FOREIGN KEY FK_A6A451407E3C61F9');
        $this->addSql('ALTER TABLE task_planning_entry DROP FOREIGN KEY FK_A6A4514012469DE2');
        $this->addSql('DROP TABLE task_planning_entry');
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB258D331FEF');
        $this->addSql('DROP INDEX task_planning_entry_date ON task');
        $this->addSql('DROP INDEX IDX_527EDB258D331FEF ON task');
        $this->addSql('ALTER TABLE task DROP planned_date, DROP planning_entry_id');
    }
}
