<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260924101607 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the step_day table: at most one step count per account per day.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE step_day (id INT AUTO_INCREMENT NOT NULL, day DATE NOT NULL, goal_in_steps INT NOT NULL, count_in_steps INT NOT NULL, source VARCHAR(32) NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, owner_id INT NOT NULL, UNIQUE INDEX step_day_owner_day (owner_id, day), INDEX IDX_77BE2C727E3C61F9 (owner_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE step_day ADD CONSTRAINT FK_77BE2C727E3C61F9 FOREIGN KEY (owner_id) REFERENCES user_account (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE step_day DROP FOREIGN KEY FK_77BE2C727E3C61F9');
        $this->addSql('DROP TABLE step_day');
    }
}
