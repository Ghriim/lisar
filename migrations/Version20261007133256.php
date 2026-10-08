<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261007133256 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Personal bests: their progression, and whether a set type counts for them.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE personal_best (id INT AUTO_INCREMENT NOT NULL, kind VARCHAR(64) NOT NULL, tier NUMERIC(10, 2) DEFAULT NULL, value NUMERIC(12, 2) NOT NULL, achieved_at DATETIME NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, owner_id INT NOT NULL, movement_id INT DEFAULT NULL, workout_id INT NOT NULL, workout_set_id INT DEFAULT NULL, INDEX personal_best_owner_movement (owner_id, movement_id), INDEX IDX_9C0771727E3C61F9 (owner_id), INDEX IDX_9C077172229E70A7 (movement_id), INDEX IDX_9C077172A6CCCFC9 (workout_id), INDEX IDX_9C077172A7812D6 (workout_set_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE personal_best ADD CONSTRAINT FK_9C0771727E3C61F9 FOREIGN KEY (owner_id) REFERENCES user_account (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE personal_best ADD CONSTRAINT FK_9C077172229E70A7 FOREIGN KEY (movement_id) REFERENCES movement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE personal_best ADD CONSTRAINT FK_9C077172A6CCCFC9 FOREIGN KEY (workout_id) REFERENCES workout (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE personal_best ADD CONSTRAINT FK_9C077172A7812D6 FOREIGN KEY (workout_set_id) REFERENCES workout_set (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE set_type ADD counts_for_personal_bests TINYINT DEFAULT 1 NOT NULL');
        // A warm-up is light on purpose: it sets no record.
        $this->addSql("UPDATE set_type SET counts_for_personal_bests = 0 WHERE name = 'Échauffement'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE personal_best DROP FOREIGN KEY FK_9C0771727E3C61F9');
        $this->addSql('ALTER TABLE personal_best DROP FOREIGN KEY FK_9C077172229E70A7');
        $this->addSql('ALTER TABLE personal_best DROP FOREIGN KEY FK_9C077172A6CCCFC9');
        $this->addSql('ALTER TABLE personal_best DROP FOREIGN KEY FK_9C077172A7812D6');
        $this->addSql('DROP TABLE personal_best');
        $this->addSql('ALTER TABLE set_type DROP counts_for_personal_bests');
    }
}
