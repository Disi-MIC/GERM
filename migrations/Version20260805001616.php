<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260805001616 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE demande_conge (id INT AUTO_INCREMENT NOT NULL, personnel_id INT NOT NULL, conge_id INT DEFAULT NULL, type VARCHAR(20) NOT NULL, date_debut DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', date_fin DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', motif LONGTEXT DEFAULT NULL, statut VARCHAR(20) NOT NULL, date_traitement DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', commentaire_traitement LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_D80610611C109075 (personnel_id), INDEX IDX_D8061061CAAC9A59 (conge_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE demande_conge ADD CONSTRAINT FK_D80610611C109075 FOREIGN KEY (personnel_id) REFERENCES personnel (id)');
        $this->addSql('ALTER TABLE demande_conge ADD CONSTRAINT FK_D8061061CAAC9A59 FOREIGN KEY (conge_id) REFERENCES conge (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE demande_conge DROP FOREIGN KEY FK_D80610611C109075');
        $this->addSql('ALTER TABLE demande_conge DROP FOREIGN KEY FK_D8061061CAAC9A59');
        $this->addSql('DROP TABLE demande_conge');
    }
}
