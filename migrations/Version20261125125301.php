<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261125125301 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Categories leave the todo list: task_category becomes category, and a catalogue habit may sit in one.';
    }

    public function up(Schema $schema): void
    {
        // Renamed rather than recreated: the foreign keys of task and task_planning_entry follow
        // the table, and only the owner key and index take the names Doctrine derives from it.
        $this->addSql('ALTER TABLE task_category DROP FOREIGN KEY FK_468CF38D7E3C61F9');
        $this->addSql('RENAME TABLE task_category TO category');
        $this->addSql('ALTER TABLE category RENAME INDEX IDX_468CF38D7E3C61F9 TO IDX_64C19C17E3C61F9');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C17E3C61F9 FOREIGN KEY (owner_id) REFERENCES user_account (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE habit ADD category_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE habit ADD CONSTRAINT FK_44FE217212469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE RESTRICT');
        $this->addSql('CREATE INDEX IDX_44FE217212469DE2 ON habit (category_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE habit DROP FOREIGN KEY FK_44FE217212469DE2');
        $this->addSql('DROP INDEX IDX_44FE217212469DE2 ON habit');
        $this->addSql('ALTER TABLE habit DROP category_id');

        $this->addSql('ALTER TABLE category DROP FOREIGN KEY FK_64C19C17E3C61F9');
        $this->addSql('RENAME TABLE category TO task_category');
        $this->addSql('ALTER TABLE task_category RENAME INDEX IDX_64C19C17E3C61F9 TO IDX_468CF38D7E3C61F9');
        $this->addSql('ALTER TABLE task_category ADD CONSTRAINT FK_468CF38D7E3C61F9 FOREIGN KEY (owner_id) REFERENCES user_account (id) ON DELETE CASCADE');
    }
}
