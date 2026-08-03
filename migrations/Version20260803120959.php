<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260803120959 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE materiel_informatique (id INT AUTO_INCREMENT NOT NULL, service_id INT NOT NULL, affecte_a_id INT DEFAULT NULL, numero_inventaire VARCHAR(30) NOT NULL, type VARCHAR(30) NOT NULL, marque VARCHAR(100) NOT NULL, modele VARCHAR(100) NOT NULL, numero_serie VARCHAR(100) DEFAULT NULL, specifications LONGTEXT DEFAULT NULL, date_acquisition DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', valeur_acquisition NUMERIC(12, 2) DEFAULT NULL, fournisseur VARCHAR(150) DEFAULT NULL, garantie_jusquau DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', etat VARCHAR(20) NOT NULL, observations LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_A0C8655BED5CA9E6 (service_id), INDEX IDX_A0C8655B4ED1378 (affecte_a_id), UNIQUE INDEX UNIQ_MATERIEL_NUM_INVENTAIRE (numero_inventaire), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE personnel (id INT AUTO_INCREMENT NOT NULL, service_id INT NOT NULL, user_id INT DEFAULT NULL, matricule VARCHAR(30) NOT NULL, nom VARCHAR(100) NOT NULL, prenom VARCHAR(100) NOT NULL, sexe VARCHAR(1) NOT NULL, date_naissance DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', fonction VARCHAR(150) NOT NULL, grade VARCHAR(100) DEFAULT NULL, type_contrat VARCHAR(20) NOT NULL, date_embauche DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', statut VARCHAR(20) NOT NULL, telephone VARCHAR(30) DEFAULT NULL, email VARCHAR(180) DEFAULT NULL, adresse LONGTEXT DEFAULT NULL, photo VARCHAR(255) DEFAULT NULL, observations LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_A6BCF3DEED5CA9E6 (service_id), UNIQUE INDEX UNIQ_A6BCF3DEA76ED395 (user_id), UNIQUE INDEX UNIQ_PERSONNEL_MATRICULE (matricule), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE service (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(20) NOT NULL, nom VARCHAR(150) NOT NULL, description LONGTEXT DEFAULT NULL, actif TINYINT(1) DEFAULT NULL, UNIQUE INDEX UNIQ_E19D9AD277153098 (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, nom VARCHAR(100) NOT NULL, prenom VARCHAR(100) NOT NULL, actif TINYINT(1) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_USER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE vehicule (id INT AUTO_INCREMENT NOT NULL, service_id INT NOT NULL, chauffeur_affecte_id INT DEFAULT NULL, immatriculation VARCHAR(20) NOT NULL, type VARCHAR(20) NOT NULL, marque VARCHAR(100) NOT NULL, modele VARCHAR(100) NOT NULL, numero_chassis VARCHAR(100) DEFAULT NULL, carburant VARCHAR(20) DEFAULT NULL, date_acquisition DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', valeur_acquisition NUMERIC(12, 2) DEFAULT NULL, kilometrage INT DEFAULT NULL, assurance_jusquau DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', visite_technique_jusquau DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', etat VARCHAR(20) NOT NULL, observations LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_292FFF1DED5CA9E6 (service_id), INDEX IDX_292FFF1D3ACCED73 (chauffeur_affecte_id), UNIQUE INDEX UNIQ_VEHICULE_IMMATRICULATION (immatriculation), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE materiel_informatique ADD CONSTRAINT FK_A0C8655BED5CA9E6 FOREIGN KEY (service_id) REFERENCES service (id)');
        $this->addSql('ALTER TABLE materiel_informatique ADD CONSTRAINT FK_A0C8655B4ED1378 FOREIGN KEY (affecte_a_id) REFERENCES personnel (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE personnel ADD CONSTRAINT FK_A6BCF3DEED5CA9E6 FOREIGN KEY (service_id) REFERENCES service (id)');
        $this->addSql('ALTER TABLE personnel ADD CONSTRAINT FK_A6BCF3DEA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE vehicule ADD CONSTRAINT FK_292FFF1DED5CA9E6 FOREIGN KEY (service_id) REFERENCES service (id)');
        $this->addSql('ALTER TABLE vehicule ADD CONSTRAINT FK_292FFF1D3ACCED73 FOREIGN KEY (chauffeur_affecte_id) REFERENCES personnel (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE materiel_informatique DROP FOREIGN KEY FK_A0C8655BED5CA9E6');
        $this->addSql('ALTER TABLE materiel_informatique DROP FOREIGN KEY FK_A0C8655B4ED1378');
        $this->addSql('ALTER TABLE personnel DROP FOREIGN KEY FK_A6BCF3DEED5CA9E6');
        $this->addSql('ALTER TABLE personnel DROP FOREIGN KEY FK_A6BCF3DEA76ED395');
        $this->addSql('ALTER TABLE vehicule DROP FOREIGN KEY FK_292FFF1DED5CA9E6');
        $this->addSql('ALTER TABLE vehicule DROP FOREIGN KEY FK_292FFF1D3ACCED73');
        $this->addSql('DROP TABLE materiel_informatique');
        $this->addSql('DROP TABLE personnel');
        $this->addSql('DROP TABLE service');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE vehicule');
    }
}
