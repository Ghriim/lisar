<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261015090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Task priorities become a fixed registry: a code on the task, no more task_priority table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE task ADD priority VARCHAR(16) DEFAULT NULL');
        // Whatever the back-office named them, a row ranks against the default: above it is high,
        // the default itself normal, below it low.
        $this->addSql(<<<'SQL'
            UPDATE task
            INNER JOIN task_priority ON task_priority.id = task.priority_id
            CROSS JOIN (SELECT MIN(weight) AS weight FROM task_priority WHERE is_default = 1) AS default_priority
            SET task.priority = CASE
                WHEN default_priority.weight IS NULL THEN 'normal'
                WHEN task_priority.weight < default_priority.weight THEN 'high'
                WHEN task_priority.weight > default_priority.weight THEN 'low'
                ELSE 'normal'
            END
            SQL);
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB25497B19F9');
        $this->addSql('DROP INDEX IDX_527EDB25497B19F9 ON task');
        $this->addSql('ALTER TABLE task DROP priority_id');
        $this->addSql('DROP TABLE task_priority');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE task_priority (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(32) NOT NULL, weight INT NOT NULL, colour VARCHAR(7) NOT NULL, is_default TINYINT DEFAULT 0 NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_2266366BEA750E8 (label), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql(<<<'SQL'
            INSERT INTO task_priority (label, weight, colour, is_default, created_at, updated_at) VALUES
                ('High', 10, '#e5484d', 0, NOW(), NOW()),
                ('Normal', 20, '#3e63dd', 1, NOW(), NOW()),
                ('Low', 30, '#889096', 0, NOW(), NOW())
            SQL);
        $this->addSql('ALTER TABLE task ADD priority_id INT DEFAULT NULL');
        $this->addSql(<<<'SQL'
            UPDATE task
            INNER JOIN task_priority ON task_priority.label = CASE task.priority
                WHEN 'high' THEN 'High'
                WHEN 'normal' THEN 'Normal'
                WHEN 'low' THEN 'Low'
            END
            SET task.priority_id = task_priority.id
            SQL);
        $this->addSql('ALTER TABLE task DROP priority');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB25497B19F9 FOREIGN KEY (priority_id) REFERENCES task_priority (id) ON DELETE RESTRICT');
        $this->addSql('CREATE INDEX IDX_527EDB25497B19F9 ON task (priority_id)');
    }
}
