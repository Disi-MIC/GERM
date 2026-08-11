<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260811114412 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE historique_affectation_materiel (id INT AUTO_INCREMENT NOT NULL, materiel_id INT NOT NULL, personnel_id INT DEFAULT NULL, date_affectation DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', date_fin_affectation DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', observations LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_5B981E6A16880AAF (materiel_id), INDEX IDX_5B981E6A1C109075 (personnel_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE maintenance (id INT AUTO_INCREMENT NOT NULL, materiel_id INT NOT NULL, realise_par_id INT DEFAULT NULL, ticket_origine_id INT DEFAULT NULL, type VARCHAR(20) NOT NULL, description LONGTEXT NOT NULL, date_realisation DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', prestataire_externe VARCHAR(150) DEFAULT NULL, cout NUMERIC(12, 2) DEFAULT NULL, observations LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_2F84F8E916880AAF (materiel_id), INDEX IDX_2F84F8E97E5383D8 (realise_par_id), INDEX IDX_2F84F8E996140BDA (ticket_origine_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ticket_incident (id INT AUTO_INCREMENT NOT NULL, personnel_id INT NOT NULL, materiel_id INT NOT NULL, assigne_a_id INT DEFAULT NULL, titre VARCHAR(150) NOT NULL, description LONGTEXT NOT NULL, priorite VARCHAR(20) NOT NULL, statut VARCHAR(20) NOT NULL, commentaire_resolution LONGTEXT DEFAULT NULL, commentaire_validation LONGTEXT DEFAULT NULL, date_prise_en_charge DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', date_resolution DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', date_cloture DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_B86A5D9B1C109075 (personnel_id), INDEX IDX_B86A5D9B16880AAF (materiel_id), INDEX IDX_B86A5D9BBB1B0F33 (assigne_a_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE historique_affectation_materiel ADD CONSTRAINT FK_5B981E6A16880AAF FOREIGN KEY (materiel_id) REFERENCES materiel_informatique (id)');
        $this->addSql('ALTER TABLE historique_affectation_materiel ADD CONSTRAINT FK_5B981E6A1C109075 FOREIGN KEY (personnel_id) REFERENCES personnel (id)');
        $this->addSql('ALTER TABLE maintenance ADD CONSTRAINT FK_2F84F8E916880AAF FOREIGN KEY (materiel_id) REFERENCES materiel_informatique (id)');
        $this->addSql('ALTER TABLE maintenance ADD CONSTRAINT FK_2F84F8E97E5383D8 FOREIGN KEY (realise_par_id) REFERENCES personnel (id)');
        $this->addSql('ALTER TABLE maintenance ADD CONSTRAINT FK_2F84F8E996140BDA FOREIGN KEY (ticket_origine_id) REFERENCES ticket_incident (id)');
        $this->addSql('ALTER TABLE ticket_incident ADD CONSTRAINT FK_B86A5D9B1C109075 FOREIGN KEY (personnel_id) REFERENCES personnel (id)');
        $this->addSql('ALTER TABLE ticket_incident ADD CONSTRAINT FK_B86A5D9B16880AAF FOREIGN KEY (materiel_id) REFERENCES materiel_informatique (id)');
        $this->addSql('ALTER TABLE ticket_incident ADD CONSTRAINT FK_B86A5D9BBB1B0F33 FOREIGN KEY (assigne_a_id) REFERENCES personnel (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE historique_affectation_materiel DROP FOREIGN KEY FK_5B981E6A16880AAF');
        $this->addSql('ALTER TABLE historique_affectation_materiel DROP FOREIGN KEY FK_5B981E6A1C109075');
        $this->addSql('ALTER TABLE maintenance DROP FOREIGN KEY FK_2F84F8E916880AAF');
        $this->addSql('ALTER TABLE maintenance DROP FOREIGN KEY FK_2F84F8E97E5383D8');
        $this->addSql('ALTER TABLE maintenance DROP FOREIGN KEY FK_2F84F8E996140BDA');
        $this->addSql('ALTER TABLE ticket_incident DROP FOREIGN KEY FK_B86A5D9B1C109075');
        $this->addSql('ALTER TABLE ticket_incident DROP FOREIGN KEY FK_B86A5D9B16880AAF');
        $this->addSql('ALTER TABLE ticket_incident DROP FOREIGN KEY FK_B86A5D9BBB1B0F33');
        $this->addSql('DROP TABLE historique_affectation_materiel');
        $this->addSql('DROP TABLE maintenance');
        $this->addSql('DROP TABLE ticket_incident');
    }
}
