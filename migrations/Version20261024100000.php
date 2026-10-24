<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261024100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Every done set counts for the personal bests: a set type no longer says whether it does.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE set_type DROP counts_for_personal_bests');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE set_type ADD counts_for_personal_bests TINYINT DEFAULT 1 NOT NULL');
    }
}
