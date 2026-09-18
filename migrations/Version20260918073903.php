<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260918073903 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the sleep_night table: at most one night per account per waking day.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE sleep_night (id INT AUTO_INCREMENT NOT NULL, day DATE NOT NULL, bedtime_at DATETIME NOT NULL, wake_up_at DATETIME NOT NULL, mood_rating INT DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, owner_id INT NOT NULL, UNIQUE INDEX sleep_night_owner_day (owner_id, day), INDEX IDX_8F2B99577E3C61F9 (owner_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE sleep_night ADD CONSTRAINT FK_8F2B99577E3C61F9 FOREIGN KEY (owner_id) REFERENCES user_account (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE sleep_night DROP FOREIGN KEY FK_8F2B99577E3C61F9');
        $this->addSql('DROP TABLE sleep_night');
    }
}
