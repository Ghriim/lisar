<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260927094059 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the movements, their families, and their secondary muscles and equipments.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE movement (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(128) NOT NULL, description LONGTEXT DEFAULT NULL, video_url VARCHAR(512) DEFAULT NULL, tracks_reps TINYINT DEFAULT 0 NOT NULL, tracks_weight TINYINT DEFAULT 0 NOT NULL, tracks_duration TINYINT DEFAULT 0 NOT NULL, tracks_distance TINYINT DEFAULT 0 NOT NULL, is_unilateral TINYINT DEFAULT 0 NOT NULL, is_active TINYINT DEFAULT 1 NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, movement_family_id INT NOT NULL, primary_muscle_id INT NOT NULL, owner_id INT DEFAULT NULL, INDEX IDX_F4DD95F7F1791AE1 (movement_family_id), INDEX IDX_F4DD95F7FFD46653 (primary_muscle_id), INDEX IDX_F4DD95F77E3C61F9 (owner_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE movement_secondary_muscle (movement_id INT NOT NULL, muscle_id INT NOT NULL, INDEX IDX_8C94D076229E70A7 (movement_id), INDEX IDX_8C94D076354FDBB4 (muscle_id), PRIMARY KEY (movement_id, muscle_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE movement_equipment (movement_id INT NOT NULL, equipment_id INT NOT NULL, INDEX IDX_E11F9908229E70A7 (movement_id), INDEX IDX_E11F9908517FE9FE (equipment_id), PRIMARY KEY (movement_id, equipment_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE movement_family (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(128) NOT NULL, is_active TINYINT DEFAULT 1 NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX movement_family_name (name), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE movement ADD CONSTRAINT FK_F4DD95F7F1791AE1 FOREIGN KEY (movement_family_id) REFERENCES movement_family (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE movement ADD CONSTRAINT FK_F4DD95F7FFD46653 FOREIGN KEY (primary_muscle_id) REFERENCES muscle (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE movement ADD CONSTRAINT FK_F4DD95F77E3C61F9 FOREIGN KEY (owner_id) REFERENCES user_account (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE movement_secondary_muscle ADD CONSTRAINT FK_8C94D076229E70A7 FOREIGN KEY (movement_id) REFERENCES movement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE movement_secondary_muscle ADD CONSTRAINT FK_8C94D076354FDBB4 FOREIGN KEY (muscle_id) REFERENCES muscle (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE movement_equipment ADD CONSTRAINT FK_E11F9908229E70A7 FOREIGN KEY (movement_id) REFERENCES movement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE movement_equipment ADD CONSTRAINT FK_E11F9908517FE9FE FOREIGN KEY (equipment_id) REFERENCES equipment (id) ON DELETE RESTRICT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE movement DROP FOREIGN KEY FK_F4DD95F7F1791AE1');
        $this->addSql('ALTER TABLE movement DROP FOREIGN KEY FK_F4DD95F7FFD46653');
        $this->addSql('ALTER TABLE movement DROP FOREIGN KEY FK_F4DD95F77E3C61F9');
        $this->addSql('ALTER TABLE movement_secondary_muscle DROP FOREIGN KEY FK_8C94D076229E70A7');
        $this->addSql('ALTER TABLE movement_secondary_muscle DROP FOREIGN KEY FK_8C94D076354FDBB4');
        $this->addSql('ALTER TABLE movement_equipment DROP FOREIGN KEY FK_E11F9908229E70A7');
        $this->addSql('ALTER TABLE movement_equipment DROP FOREIGN KEY FK_E11F9908517FE9FE');
        $this->addSql('DROP TABLE movement');
        $this->addSql('DROP TABLE movement_secondary_muscle');
        $this->addSql('DROP TABLE movement_equipment');
        $this->addSql('DROP TABLE movement_family');
    }
}
