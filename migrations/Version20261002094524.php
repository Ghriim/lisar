<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261002094524 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the workout set types.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE set_type (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(128) NOT NULL, colour VARCHAR(32) NOT NULL, is_active TINYINT DEFAULT 1 NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX set_type_name (name), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE set_type');
    }
}
