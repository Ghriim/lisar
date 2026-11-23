<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261024150257 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Routines: the blueprint of a workout, its blocks, movements and prescribed sets.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE routine (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, owner_id INT DEFAULT NULL, INDEX routine_owner_name (owner_id, name), INDEX IDX_4BF6D8D67E3C61F9 (owner_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE routine_block (id INT AUTO_INCREMENT NOT NULL, position INT NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, routine_id INT NOT NULL, INDEX IDX_AB039B4EF27A94C7 (routine_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE routine_exercise (id INT AUTO_INCREMENT NOT NULL, position INT NOT NULL, note LONGTEXT DEFAULT NULL, rest_in_seconds INT DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, routine_block_id INT NOT NULL, movement_id INT NOT NULL, INDEX IDX_50CE302A17F9673A (routine_block_id), INDEX IDX_50CE302A229E70A7 (movement_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE routine_set (id INT AUTO_INCREMENT NOT NULL, position INT NOT NULL, reps INT DEFAULT NULL, percentage_of_one_rep_max DOUBLE PRECISION DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, routine_exercise_id INT NOT NULL, INDEX IDX_883260C8F001DF12 (routine_exercise_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE routine ADD CONSTRAINT FK_4BF6D8D67E3C61F9 FOREIGN KEY (owner_id) REFERENCES user_account (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE routine_block ADD CONSTRAINT FK_AB039B4EF27A94C7 FOREIGN KEY (routine_id) REFERENCES routine (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE routine_exercise ADD CONSTRAINT FK_50CE302A17F9673A FOREIGN KEY (routine_block_id) REFERENCES routine_block (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE routine_exercise ADD CONSTRAINT FK_50CE302A229E70A7 FOREIGN KEY (movement_id) REFERENCES movement (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE routine_set ADD CONSTRAINT FK_883260C8F001DF12 FOREIGN KEY (routine_exercise_id) REFERENCES routine_exercise (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE routine DROP FOREIGN KEY FK_4BF6D8D67E3C61F9');
        $this->addSql('ALTER TABLE routine_block DROP FOREIGN KEY FK_AB039B4EF27A94C7');
        $this->addSql('ALTER TABLE routine_exercise DROP FOREIGN KEY FK_50CE302A17F9673A');
        $this->addSql('ALTER TABLE routine_exercise DROP FOREIGN KEY FK_50CE302A229E70A7');
        $this->addSql('ALTER TABLE routine_set DROP FOREIGN KEY FK_883260C8F001DF12');
        $this->addSql('DROP TABLE routine');
        $this->addSql('DROP TABLE routine_block');
        $this->addSql('DROP TABLE routine_exercise');
        $this->addSql('DROP TABLE routine_set');
    }
}
