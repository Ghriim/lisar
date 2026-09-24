<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260924123854 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the habit catalogue, its subscriptions, and the per-subscription daily entries.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE habit (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(128) NOT NULL, icon VARCHAR(32) NOT NULL, source_kind VARCHAR(16) NOT NULL, tracker_kind VARCHAR(32) DEFAULT NULL, tracker_threshold INT DEFAULT NULL, is_active TINYINT DEFAULT 1 NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE habit_entry (id INT AUTO_INCREMENT NOT NULL, day DATE NOT NULL, is_completed TINYINT DEFAULT 0 NOT NULL, source VARCHAR(32) NOT NULL, completed_at DATETIME DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, subscription_id INT NOT NULL, UNIQUE INDEX habit_entry_subscription_day (subscription_id, day), INDEX IDX_A789F1A9A1887DC (subscription_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE habit_subscription (id INT AUTO_INCREMENT NOT NULL, is_active TINYINT DEFAULT 1 NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, owner_id INT NOT NULL, habit_id INT NOT NULL, UNIQUE INDEX habit_subscription_owner_habit (owner_id, habit_id), INDEX IDX_EC45B3927E3C61F9 (owner_id), INDEX IDX_EC45B392E7AEB3B2 (habit_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE habit_entry ADD CONSTRAINT FK_A789F1A9A1887DC FOREIGN KEY (subscription_id) REFERENCES habit_subscription (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE habit_subscription ADD CONSTRAINT FK_EC45B3927E3C61F9 FOREIGN KEY (owner_id) REFERENCES user_account (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE habit_subscription ADD CONSTRAINT FK_EC45B392E7AEB3B2 FOREIGN KEY (habit_id) REFERENCES habit (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE habit_entry DROP FOREIGN KEY FK_A789F1A9A1887DC');
        $this->addSql('ALTER TABLE habit_subscription DROP FOREIGN KEY FK_EC45B3927E3C61F9');
        $this->addSql('ALTER TABLE habit_subscription DROP FOREIGN KEY FK_EC45B392E7AEB3B2');
        $this->addSql('DROP TABLE habit');
        $this->addSql('DROP TABLE habit_entry');
        $this->addSql('DROP TABLE habit_subscription');
    }
}
