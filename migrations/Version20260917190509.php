<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260917190509 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the hydration tables: hydration_preset, hydration_day, hydration_entry.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE hydration_day (id INT AUTO_INCREMENT NOT NULL, day DATE NOT NULL, goal_in_millilitres INT NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, owner_id INT NOT NULL, UNIQUE INDEX hydration_day_owner_day (owner_id, day), INDEX IDX_DBD5DCA57E3C61F9 (owner_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE hydration_entry (id INT AUTO_INCREMENT NOT NULL, recorded_at DATETIME NOT NULL, volume_in_millilitres INT NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, hydration_day_id INT NOT NULL, INDEX IDX_44C7672062C6F628 (hydration_day_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE hydration_preset (id INT AUTO_INCREMENT NOT NULL, icon VARCHAR(32) NOT NULL, volume_in_millilitres INT NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE hydration_day ADD CONSTRAINT FK_DBD5DCA57E3C61F9 FOREIGN KEY (owner_id) REFERENCES user_account (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE hydration_entry ADD CONSTRAINT FK_44C7672062C6F628 FOREIGN KEY (hydration_day_id) REFERENCES hydration_day (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE hydration_day DROP FOREIGN KEY FK_DBD5DCA57E3C61F9');
        $this->addSql('ALTER TABLE hydration_entry DROP FOREIGN KEY FK_44C7672062C6F628');
        $this->addSql('DROP TABLE hydration_day');
        $this->addSql('DROP TABLE hydration_entry');
        $this->addSql('DROP TABLE hydration_preset');
    }
}
