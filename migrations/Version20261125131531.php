<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261125131531 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'The icon moves from the habit to the category: a common category wears one, and everything filed in it wears that.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE category ADD icon VARCHAR(32) DEFAULT NULL');
        // Every common category has an icon from now on; the existing ones start on a neutral one.
        $this->addSql("UPDATE category SET icon = 'target' WHERE owner_id IS NULL");
        $this->addSql('ALTER TABLE habit DROP icon');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE habit ADD icon VARCHAR(32) NOT NULL DEFAULT 'target'");
        $this->addSql('ALTER TABLE category DROP icon');
    }
}
