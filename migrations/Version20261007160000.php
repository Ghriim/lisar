<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261007160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'The rest planned after each set of a movement in a workout.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workout_exercise ADD rest_in_seconds INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workout_exercise DROP rest_in_seconds');
    }
}
