<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260917123145 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the todo list tables: task, task_priority, task_category, task_tag.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE task (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, due_date DATE DEFAULT NULL, completed_at DATETIME DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, owner_id INT NOT NULL, priority_id INT DEFAULT NULL, category_id INT DEFAULT NULL, parent_id INT DEFAULT NULL, INDEX IDX_527EDB257E3C61F9 (owner_id), INDEX IDX_527EDB25497B19F9 (priority_id), INDEX IDX_527EDB2512469DE2 (category_id), INDEX IDX_527EDB25727ACA70 (parent_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE task_tag_assignment (task_id INT NOT NULL, task_tag_id INT NOT NULL, INDEX IDX_89F7B0448DB60186 (task_id), INDEX IDX_89F7B044817BE7C2 (task_tag_id), PRIMARY KEY (task_id, task_tag_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE task_category (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(32) NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, owner_id INT DEFAULT NULL, INDEX IDX_468CF38D7E3C61F9 (owner_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE task_priority (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(32) NOT NULL, weight INT NOT NULL, colour VARCHAR(7) NOT NULL, is_default TINYINT DEFAULT 0 NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_2266366BEA750E8 (label), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE task_tag (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(32) NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, owner_id INT NOT NULL, UNIQUE INDEX task_tag_owner_label (owner_id, label), INDEX IDX_6C0B4F047E3C61F9 (owner_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB257E3C61F9 FOREIGN KEY (owner_id) REFERENCES user_account (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB25497B19F9 FOREIGN KEY (priority_id) REFERENCES task_priority (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB2512469DE2 FOREIGN KEY (category_id) REFERENCES task_category (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB25727ACA70 FOREIGN KEY (parent_id) REFERENCES task (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE task_tag_assignment ADD CONSTRAINT FK_89F7B0448DB60186 FOREIGN KEY (task_id) REFERENCES task (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE task_tag_assignment ADD CONSTRAINT FK_89F7B044817BE7C2 FOREIGN KEY (task_tag_id) REFERENCES task_tag (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE task_category ADD CONSTRAINT FK_468CF38D7E3C61F9 FOREIGN KEY (owner_id) REFERENCES user_account (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE task_tag ADD CONSTRAINT FK_6C0B4F047E3C61F9 FOREIGN KEY (owner_id) REFERENCES user_account (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB257E3C61F9');
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB25497B19F9');
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB2512469DE2');
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB25727ACA70');
        $this->addSql('ALTER TABLE task_tag_assignment DROP FOREIGN KEY FK_89F7B0448DB60186');
        $this->addSql('ALTER TABLE task_tag_assignment DROP FOREIGN KEY FK_89F7B044817BE7C2');
        $this->addSql('ALTER TABLE task_category DROP FOREIGN KEY FK_468CF38D7E3C61F9');
        $this->addSql('ALTER TABLE task_tag DROP FOREIGN KEY FK_6C0B4F047E3C61F9');
        $this->addSql('DROP TABLE task');
        $this->addSql('DROP TABLE task_tag_assignment');
        $this->addSql('DROP TABLE task_category');
        $this->addSql('DROP TABLE task_priority');
        $this->addSql('DROP TABLE task_tag');
    }
}
