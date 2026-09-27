<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260927081916 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the workout reference data: equipments, muscle groups, and muscles.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE equipment (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(128) NOT NULL, has_weight TINYINT DEFAULT 0 NOT NULL, has_distance TINYINT DEFAULT 0 NOT NULL, is_active TINYINT DEFAULT 1 NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX equipment_name (name), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE muscle (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(128) NOT NULL, is_active TINYINT DEFAULT 1 NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, muscle_group_id INT NOT NULL, UNIQUE INDEX muscle_name (name), INDEX IDX_F31119EF44004D0 (muscle_group_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE muscle_group (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(128) NOT NULL, is_active TINYINT DEFAULT 1 NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX muscle_group_name (name), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE muscle ADD CONSTRAINT FK_F31119EF44004D0 FOREIGN KEY (muscle_group_id) REFERENCES muscle_group (id) ON DELETE RESTRICT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE muscle DROP FOREIGN KEY FK_F31119EF44004D0');
        $this->addSql('DROP TABLE equipment');
        $this->addSql('DROP TABLE muscle');
        $this->addSql('DROP TABLE muscle_group');
    }
}
