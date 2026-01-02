<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240211134115 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ResetPasswordRequest (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, selector VARCHAR(20) NOT NULL, hashedToken VARCHAR(100) NOT NULL, requestedAt DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', expiresAt DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_35370143A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ResetPasswordRequest ADD CONSTRAINT FK_35370143A76ED395 FOREIGN KEY (user_id) REFERENCES User (id)');
        $this->addSql('ALTER TABLE DataFile CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE pluginId pluginId INT DEFAULT NULL, CHANGE rootId rootId INT DEFAULT NULL');
        $this->addSql('ALTER TABLE DataFile ADD CONSTRAINT FK_DC3C0337B7939B21 FOREIGN KEY (rootId) REFERENCES DataNode (id)');
        $this->addSql('ALTER TABLE DataFile ADD CONSTRAINT FK_DC3C03379A9A50E9 FOREIGN KEY (pluginId) REFERENCES Plugin (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DC3C0337B7939B21 ON DataFile (rootId)');
        $this->addSql('CREATE INDEX IDX_DC3C03379A9A50E9 ON DataFile (pluginId)');
        $this->addSql('ALTER TABLE Plugin CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE description description LONGTEXT NOT NULL, CHANGE dateCreated dateCreated DATETIME NOT NULL, CHANGE authorId authorId INT NOT NULL');
        $this->addSql('ALTER TABLE Plugin ADD CONSTRAINT FK_EEC222A2A196F9FD FOREIGN KEY (authorId) REFERENCES User (id)');
        $this->addSql('CREATE INDEX IDX_EEC222A2A196F9FD ON Plugin (authorId)');
        $this->addSql('ALTER TABLE User DROP dateCreated, CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE username username VARCHAR(180) NOT NULL, CHANGE roles roles LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2DA17977F85E0677 ON User (username)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ResetPasswordRequest DROP FOREIGN KEY FK_35370143A76ED395');
        $this->addSql('DROP TABLE ResetPasswordRequest');
        $this->addSql('ALTER TABLE DataFile DROP FOREIGN KEY FK_DC3C0337B7939B21');
        $this->addSql('ALTER TABLE DataFile DROP FOREIGN KEY FK_DC3C03379A9A50E9');
        $this->addSql('DROP INDEX UNIQ_DC3C0337B7939B21 ON DataFile');
        $this->addSql('DROP INDEX IDX_DC3C03379A9A50E9 ON DataFile');
        $this->addSql('ALTER TABLE DataFile CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL, CHANGE rootId rootId INT UNSIGNED DEFAULT NULL, CHANGE pluginId pluginId INT UNSIGNED DEFAULT NULL');
        $this->addSql('DROP INDEX UNIQ_2DA17977F85E0677 ON User');
        $this->addSql('ALTER TABLE User ADD dateCreated DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL, CHANGE username username VARCHAR(255) NOT NULL, CHANGE roles roles VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE Plugin DROP FOREIGN KEY FK_EEC222A2A196F9FD');
        $this->addSql('DROP INDEX IDX_EEC222A2A196F9FD ON Plugin');
        $this->addSql('ALTER TABLE Plugin CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL, CHANGE dateCreated dateCreated DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE description description TEXT NOT NULL, CHANGE authorId authorId INT UNSIGNED DEFAULT NULL');
    }
}
