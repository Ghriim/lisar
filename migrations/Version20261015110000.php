<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261015110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Decimal measures become DOUBLE columns: read back as the float that was written.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE weight_entry CHANGE weight_in_kilograms weight_in_kilograms DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE workout_set CHANGE weight_in_kilograms weight_in_kilograms DOUBLE PRECISION DEFAULT NULL, CHANGE rpe rpe DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE personal_best CHANGE tier tier DOUBLE PRECISION DEFAULT NULL, CHANGE value value DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE workout_summary CHANGE volume_in_kilograms volume_in_kilograms DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE weight_entry CHANGE weight_in_kilograms weight_in_kilograms NUMERIC(5, 2) NOT NULL');
        $this->addSql('ALTER TABLE workout_set CHANGE weight_in_kilograms weight_in_kilograms NUMERIC(6, 2) DEFAULT NULL, CHANGE rpe rpe NUMERIC(3, 1) DEFAULT NULL');
        $this->addSql('ALTER TABLE personal_best CHANGE tier tier NUMERIC(10, 2) DEFAULT NULL, CHANGE value value NUMERIC(12, 2) NOT NULL');
        $this->addSql('ALTER TABLE workout_summary CHANGE volume_in_kilograms volume_in_kilograms NUMERIC(12, 2) DEFAULT NULL');
    }
}
