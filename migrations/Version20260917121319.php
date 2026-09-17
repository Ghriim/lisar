<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260917121319 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the user_comment table: the back-office note thread on an account.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_comment (id INT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, user_account_id INT NOT NULL, author_id INT NOT NULL, INDEX IDX_CC794C663C0C9956 (user_account_id), INDEX IDX_CC794C66F675F31B (author_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE user_comment ADD CONSTRAINT FK_CC794C663C0C9956 FOREIGN KEY (user_account_id) REFERENCES user_account (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_comment ADD CONSTRAINT FK_CC794C66F675F31B FOREIGN KEY (author_id) REFERENCES user_account (id) ON DELETE RESTRICT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_comment DROP FOREIGN KEY FK_CC794C663C0C9956');
        $this->addSql('ALTER TABLE user_comment DROP FOREIGN KEY FK_CC794C66F675F31B');
        $this->addSql('DROP TABLE user_comment');
    }
}
