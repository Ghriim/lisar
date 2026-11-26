<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261126144843 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'TODOs: the lists tasks sit in, a default « A faire » per account; a task and a task planning entry each go in one, and a task may be starred. Schema only: there is no data to carry over.';
    }

    public function up(Schema $schema): void
    {
        // The title's case-insensitive collation is what makes the unique index ignore case.
        $this->addSql('CREATE TABLE todo (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(125) NOT NULL, status VARCHAR(16) NOT NULL, is_default TINYINT NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, owner_id INT NOT NULL, UNIQUE INDEX todo_owner_title (owner_id, title), INDEX IDX_5A0EB6A07E3C61F9 (owner_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE todo ADD CONSTRAINT FK_5A0EB6A07E3C61F9 FOREIGN KEY (owner_id) REFERENCES user_account (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE task ADD is_favorite TINYINT DEFAULT 0 NOT NULL, ADD todo_id INT NOT NULL');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB25EA1EBC33 FOREIGN KEY (todo_id) REFERENCES todo (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_527EDB25EA1EBC33 ON task (todo_id)');
        $this->addSql('ALTER TABLE task_planning_entry ADD todo_id INT NOT NULL');
        $this->addSql('ALTER TABLE task_planning_entry ADD CONSTRAINT FK_A6A45140EA1EBC33 FOREIGN KEY (todo_id) REFERENCES todo (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_A6A45140EA1EBC33 ON task_planning_entry (todo_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE todo DROP FOREIGN KEY FK_5A0EB6A07E3C61F9');
        $this->addSql('DROP TABLE todo');
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB25EA1EBC33');
        $this->addSql('DROP INDEX IDX_527EDB25EA1EBC33 ON task');
        $this->addSql('ALTER TABLE task DROP is_favorite, DROP todo_id');
        $this->addSql('ALTER TABLE task_planning_entry DROP FOREIGN KEY FK_A6A45140EA1EBC33');
        $this->addSql('DROP INDEX IDX_A6A45140EA1EBC33 ON task_planning_entry');
        $this->addSql('ALTER TABLE task_planning_entry DROP todo_id');
    }
}
