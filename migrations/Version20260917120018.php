<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260917120018 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the user_session table, one row per live sign-in.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_session (id INT AUTO_INCREMENT NOT NULL, refresh_token_hash VARCHAR(64) NOT NULL, expires_at DATETIME NOT NULL, revoked_at DATETIME DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, user_account_id INT NOT NULL, UNIQUE INDEX UNIQ_8849CBDE7288F9EC (refresh_token_hash), INDEX IDX_8849CBDE3C0C9956 (user_account_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE user_session ADD CONSTRAINT FK_8849CBDE3C0C9956 FOREIGN KEY (user_account_id) REFERENCES user_account (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_session DROP FOREIGN KEY FK_8849CBDE3C0C9956');
        $this->addSql('DROP TABLE user_session');
    }
}
