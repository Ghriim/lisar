<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260917113725 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the user_account and user_identity tables.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_account (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(32) NOT NULL, email VARCHAR(180) NOT NULL, avatar VARCHAR(255) DEFAULT NULL, role VARCHAR(32) DEFAULT \'ROLE_USER\' NOT NULL, is_active TINYINT DEFAULT 1 NOT NULL, last_signed_in_at DATETIME DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_253B48AEF85E0677 (username), UNIQUE INDEX UNIQ_253B48AEE7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user_identity (id INT AUTO_INCREMENT NOT NULL, provider VARCHAR(32) NOT NULL, external_id VARCHAR(255) DEFAULT NULL, password_hash VARCHAR(255) DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, user_account_id INT NOT NULL, UNIQUE INDEX user_identity_user_provider (user_account_id, provider), UNIQUE INDEX user_identity_provider_external_id (provider, external_id), INDEX IDX_8A180DC43C0C9956 (user_account_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE user_identity ADD CONSTRAINT FK_8A180DC43C0C9956 FOREIGN KEY (user_account_id) REFERENCES user_account (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_identity DROP FOREIGN KEY FK_8A180DC43C0C9956');
        $this->addSql('DROP TABLE user_account');
        $this->addSql('DROP TABLE user_identity');
    }
}
