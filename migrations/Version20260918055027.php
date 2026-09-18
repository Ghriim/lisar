<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260918055027 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the weight_entry table: at most one weight per account per day.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE weight_entry (id INT AUTO_INCREMENT NOT NULL, day DATE NOT NULL, recorded_at DATETIME NOT NULL, weight_in_kilograms NUMERIC(5, 2) NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, owner_id INT NOT NULL, UNIQUE INDEX weight_entry_owner_day (owner_id, day), INDEX IDX_1486C8C07E3C61F9 (owner_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE weight_entry ADD CONSTRAINT FK_1486C8C07E3C61F9 FOREIGN KEY (owner_id) REFERENCES user_account (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE weight_entry DROP FOREIGN KEY FK_1486C8C07E3C61F9');
        $this->addSql('DROP TABLE weight_entry');
    }
}
