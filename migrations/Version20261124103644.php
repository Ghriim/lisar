<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261124103644 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Habit planning entries: the days a subscribed habit counts, on a rule of dates.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE habit_planning_entry (id INT AUTO_INCREMENT NOT NULL, rule VARCHAR(32) NOT NULL, start_date DATE NOT NULL, end_date DATE DEFAULT NULL, weekdays JSON NOT NULL, week_interval INT NOT NULL, month_weeks JSON NOT NULL, month_days JSON NOT NULL, start_time_in_minutes INT DEFAULT NULL, duration_in_minutes INT DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, owner_id INT NOT NULL, subscription_id INT NOT NULL, INDEX habit_planning_entry_owner (owner_id), INDEX IDX_4B58515F9A1887DC (subscription_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE habit_planning_entry ADD CONSTRAINT FK_4B58515F7E3C61F9 FOREIGN KEY (owner_id) REFERENCES user_account (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE habit_planning_entry ADD CONSTRAINT FK_4B58515F9A1887DC FOREIGN KEY (subscription_id) REFERENCES habit_subscription (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE habit_planning_entry DROP FOREIGN KEY FK_4B58515F7E3C61F9');
        $this->addSql('ALTER TABLE habit_planning_entry DROP FOREIGN KEY FK_4B58515F9A1887DC');
        $this->addSql('DROP TABLE habit_planning_entry');
    }
}
