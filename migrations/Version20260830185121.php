<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260830185121 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE sub_task (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(150) NOT NULL, is_completed TINYINT DEFAULT NULL, completed_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE tag (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(40) NOT NULL, description LONGTEXT DEFAULT NULL, color VARCHAR(20) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, position INT DEFAULT NULL, owner_id INT DEFAULT NULL, INDEX IDX_389B7837E3C61F9 (owner_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE task (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, is_completed TINYINT DEFAULT 0 NOT NULL, completed_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, due_date DATETIME DEFAULT NULL, description LONGTEXT DEFAULT NULL, owner_id INT DEFAULT NULL, tag_id INT DEFAULT NULL, sub_task_id INT DEFAULT NULL, INDEX IDX_527EDB257E3C61F9 (owner_id), INDEX IDX_527EDB25BAD26311 (tag_id), INDEX IDX_527EDB25F26E5D72 (sub_task_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, username VARCHAR(80) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, avatar VARCHAR(255) DEFAULT NULL, is_verified TINYINT DEFAULT NULL, verified_at DATETIME DEFAULT NULL, preferences JSON DEFAULT NULL, firstname VARCHAR(80) DEFAULT NULL, lastname VARCHAR(80) DEFAULT NULL, sub_task_id INT NOT NULL, INDEX IDX_8D93D649F26E5D72 (sub_task_id), UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), UNIQUE INDEX UNIQ_IDENTIFIER_USERNAME (username), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user_request (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(50) NOT NULL, token VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, expires_at DATETIME DEFAULT NULL, used_at DATETIME DEFAULT NULL, is_used TINYINT DEFAULT NULL, content JSON DEFAULT NULL, user_id INT DEFAULT NULL, INDEX IDX_639A9195A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE tag ADD CONSTRAINT FK_389B7837E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB257E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB25BAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id)');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB25F26E5D72 FOREIGN KEY (sub_task_id) REFERENCES sub_task (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649F26E5D72 FOREIGN KEY (sub_task_id) REFERENCES sub_task (id)');
        $this->addSql('ALTER TABLE user_request ADD CONSTRAINT FK_639A9195A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tag DROP FOREIGN KEY FK_389B7837E3C61F9');
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB257E3C61F9');
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB25BAD26311');
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB25F26E5D72');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649F26E5D72');
        $this->addSql('ALTER TABLE user_request DROP FOREIGN KEY FK_639A9195A76ED395');
        $this->addSql('DROP TABLE sub_task');
        $this->addSql('DROP TABLE tag');
        $this->addSql('DROP TABLE task');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE user_request');
    }
}
