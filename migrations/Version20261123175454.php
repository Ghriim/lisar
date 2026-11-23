<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261123175454 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'A workout remembers the routine it was started from, while that routine exists.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workout ADD routine_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE workout ADD CONSTRAINT FK_649FFB72F27A94C7 FOREIGN KEY (routine_id) REFERENCES routine (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_649FFB72F27A94C7 ON workout (routine_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workout DROP FOREIGN KEY FK_649FFB72F27A94C7');
        $this->addSql('DROP INDEX IDX_649FFB72F27A94C7 ON workout');
        $this->addSql('ALTER TABLE workout DROP routine_id');
    }
}
