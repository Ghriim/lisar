<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261124172915 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Gamification: the experience gains, and the physical stats each movement feeds.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE experience_gain (id INT AUTO_INCREMENT NOT NULL, stat VARCHAR(16) NOT NULL, amount INT NOT NULL, earned_at DATETIME NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, owner_id INT NOT NULL, INDEX experience_gain_owner_stat (owner_id, stat), INDEX IDX_8485E21D7E3C61F9 (owner_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE experience_gain ADD CONSTRAINT FK_8485E21D7E3C61F9 FOREIGN KEY (owner_id) REFERENCES user_account (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE movement ADD primary_stat VARCHAR(16) NOT NULL, ADD secondary_stats JSON NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE experience_gain DROP FOREIGN KEY FK_8485E21D7E3C61F9');
        $this->addSql('DROP TABLE experience_gain');
        $this->addSql('ALTER TABLE movement DROP primary_stat, DROP secondary_stats');
    }
}
