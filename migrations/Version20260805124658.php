<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260805124658 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE decision_conge (id INT AUTO_INCREMENT NOT NULL, personnel_id INT NOT NULL, numero_decision VARCHAR(100) NOT NULL, date_decision DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', date_expiration DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', observations LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_99D54411C109075 (personnel_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE demande_decision (id INT AUTO_INCREMENT NOT NULL, personnel_id INT NOT NULL, decision_creee_id INT DEFAULT NULL, date_derniere_decision DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', numero_derniere_decision VARCHAR(100) DEFAULT NULL, motif LONGTEXT DEFAULT NULL, statut VARCHAR(20) NOT NULL, date_traitement DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', commentaire_traitement LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_C2F5F78A1C109075 (personnel_id), INDEX IDX_C2F5F78A517E0BCA (decision_creee_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE demande_jouissance (id INT AUTO_INCREMENT NOT NULL, personnel_id INT NOT NULL, decision_id INT DEFAULT NULL, conge_id INT DEFAULT NULL, type VARCHAR(20) NOT NULL, date_debut DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', date_fin DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', motif LONGTEXT DEFAULT NULL, statut VARCHAR(20) NOT NULL, date_traitement DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', commentaire_traitement LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_2BD810561C109075 (personnel_id), INDEX IDX_2BD81056BDEE7539 (decision_id), INDEX IDX_2BD81056CAAC9A59 (conge_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE piece_justificative_decision (id INT AUTO_INCREMENT NOT NULL, demande_id INT NOT NULL, chemin_fichier VARCHAR(255) NOT NULL, nom_original VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_C68B5EAF80E95E18 (demande_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE piece_justificative_jouissance (id INT AUTO_INCREMENT NOT NULL, demande_id INT NOT NULL, chemin_fichier VARCHAR(255) NOT NULL, nom_original VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_6C21DF8380E95E18 (demande_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE decision_conge ADD CONSTRAINT FK_99D54411C109075 FOREIGN KEY (personnel_id) REFERENCES personnel (id)');
        $this->addSql('ALTER TABLE demande_decision ADD CONSTRAINT FK_C2F5F78A1C109075 FOREIGN KEY (personnel_id) REFERENCES personnel (id)');
        $this->addSql('ALTER TABLE demande_decision ADD CONSTRAINT FK_C2F5F78A517E0BCA FOREIGN KEY (decision_creee_id) REFERENCES decision_conge (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE demande_jouissance ADD CONSTRAINT FK_2BD810561C109075 FOREIGN KEY (personnel_id) REFERENCES personnel (id)');
        $this->addSql('ALTER TABLE demande_jouissance ADD CONSTRAINT FK_2BD81056BDEE7539 FOREIGN KEY (decision_id) REFERENCES decision_conge (id)');
        $this->addSql('ALTER TABLE demande_jouissance ADD CONSTRAINT FK_2BD81056CAAC9A59 FOREIGN KEY (conge_id) REFERENCES conge (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE piece_justificative_decision ADD CONSTRAINT FK_C68B5EAF80E95E18 FOREIGN KEY (demande_id) REFERENCES demande_decision (id)');
        $this->addSql('ALTER TABLE piece_justificative_jouissance ADD CONSTRAINT FK_6C21DF8380E95E18 FOREIGN KEY (demande_id) REFERENCES demande_jouissance (id)');
        $this->addSql('ALTER TABLE demande_conge DROP FOREIGN KEY FK_D80610611C109075');
        $this->addSql('ALTER TABLE demande_conge DROP FOREIGN KEY FK_D8061061CAAC9A59');
        $this->addSql('DROP TABLE demande_conge');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE demande_conge (id INT AUTO_INCREMENT NOT NULL, personnel_id INT NOT NULL, conge_id INT DEFAULT NULL, type VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, date_debut DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', date_fin DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', motif LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, statut VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, date_traitement DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', commentaire_traitement LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_D80610611C109075 (personnel_id), INDEX IDX_D8061061CAAC9A59 (conge_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE demande_conge ADD CONSTRAINT FK_D80610611C109075 FOREIGN KEY (personnel_id) REFERENCES personnel (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE demande_conge ADD CONSTRAINT FK_D8061061CAAC9A59 FOREIGN KEY (conge_id) REFERENCES conge (id) ON UPDATE NO ACTION ON DELETE SET NULL');
        $this->addSql('ALTER TABLE decision_conge DROP FOREIGN KEY FK_99D54411C109075');
        $this->addSql('ALTER TABLE demande_decision DROP FOREIGN KEY FK_C2F5F78A1C109075');
        $this->addSql('ALTER TABLE demande_decision DROP FOREIGN KEY FK_C2F5F78A517E0BCA');
        $this->addSql('ALTER TABLE demande_jouissance DROP FOREIGN KEY FK_2BD810561C109075');
        $this->addSql('ALTER TABLE demande_jouissance DROP FOREIGN KEY FK_2BD81056BDEE7539');
        $this->addSql('ALTER TABLE demande_jouissance DROP FOREIGN KEY FK_2BD81056CAAC9A59');
        $this->addSql('ALTER TABLE piece_justificative_decision DROP FOREIGN KEY FK_C68B5EAF80E95E18');
        $this->addSql('ALTER TABLE piece_justificative_jouissance DROP FOREIGN KEY FK_6C21DF8380E95E18');
        $this->addSql('DROP TABLE decision_conge');
        $this->addSql('DROP TABLE demande_decision');
        $this->addSql('DROP TABLE demande_jouissance');
        $this->addSql('DROP TABLE piece_justificative_decision');
        $this->addSql('DROP TABLE piece_justificative_jouissance');
    }
}
