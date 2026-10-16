<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261015100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Tasks no longer carry tags: drop task_tag and task_tag_assignment.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE task_tag_assignment DROP FOREIGN KEY FK_89F7B0448DB60186');
        $this->addSql('ALTER TABLE task_tag_assignment DROP FOREIGN KEY FK_89F7B044817BE7C2');
        $this->addSql('ALTER TABLE task_tag DROP FOREIGN KEY FK_6C0B4F047E3C61F9');
        $this->addSql('DROP TABLE task_tag_assignment');
        $this->addSql('DROP TABLE task_tag');
    }

    // The tables come back empty: the tags themselves are gone with the up.
    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE task_tag (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(32) NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, owner_id INT NOT NULL, UNIQUE INDEX task_tag_owner_label (owner_id, label), INDEX IDX_6C0B4F047E3C61F9 (owner_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE task_tag_assignment (task_id INT NOT NULL, task_tag_id INT NOT NULL, INDEX IDX_89F7B0448DB60186 (task_id), INDEX IDX_89F7B044817BE7C2 (task_tag_id), PRIMARY KEY (task_id, task_tag_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE task_tag ADD CONSTRAINT FK_6C0B4F047E3C61F9 FOREIGN KEY (owner_id) REFERENCES user_account (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE task_tag_assignment ADD CONSTRAINT FK_89F7B0448DB60186 FOREIGN KEY (task_id) REFERENCES task (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE task_tag_assignment ADD CONSTRAINT FK_89F7B044817BE7C2 FOREIGN KEY (task_tag_id) REFERENCES task_tag (id) ON DELETE CASCADE');
    }
}
