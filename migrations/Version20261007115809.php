<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261007115809 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'A set always carries a set type: the default one, « Travail », when none is named.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE set_type ADD is_default_type TINYINT DEFAULT 0 NOT NULL');
        // A « Travail » row already there becomes the default rather than colliding on the name.
        $this->addSql("INSERT INTO set_type (name, colour, is_active, is_default_type, created_at, updated_at) VALUES ('Travail', 'blue', 1, 1, NOW(), NOW()) ON DUPLICATE KEY UPDATE is_default_type = 1, is_active = 1");
        // The sets logged without a type were ordinary working sets: that is what the default says.
        $this->addSql('UPDATE workout_set SET set_type_id = (SELECT id FROM set_type WHERE is_default_type = 1) WHERE set_type_id IS NULL');
        $this->addSql('ALTER TABLE workout_set CHANGE set_type_id set_type_id INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workout_set CHANGE set_type_id set_type_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE set_type DROP is_default_type');
    }
}
