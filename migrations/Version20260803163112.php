<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260803163112 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE direction (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(20) NOT NULL, nom VARCHAR(150) NOT NULL, description LONGTEXT DEFAULT NULL, actif TINYINT(1) DEFAULT NULL, UNIQUE INDEX UNIQ_3E4AD1B377153098 (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE liste_valeur (id INT AUTO_INCREMENT NOT NULL, categorie VARCHAR(30) NOT NULL, code VARCHAR(50) NOT NULL, libelle VARCHAR(150) NOT NULL, actif TINYINT(1) DEFAULT NULL, UNIQUE INDEX UNIQ_LISTE_VALEUR_CAT_CODE (categorie, code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql(<<<'SQL'
            INSERT INTO liste_valeur (categorie, code, libelle, actif) VALUES
                ('type-materiel', 'ordinateur_bureau', 'Ordinateur de bureau', 1),
                ('type-materiel', 'ordinateur_portable', 'Ordinateur portable', 1),
                ('type-materiel', 'imprimante', 'Imprimante', 1),
                ('type-materiel', 'serveur', 'Serveur', 1),
                ('type-materiel', 'routeur', 'Routeur', 1),
                ('type-materiel', 'switch', 'Switch', 1),
                ('type-materiel', 'scanner', 'Scanner', 1),
                ('type-materiel', 'onduleur', 'Onduleur', 1),
                ('type-materiel', 'videoprojecteur', 'Vidéoprojecteur', 1),
                ('type-materiel', 'telephone', 'Téléphone', 1),
                ('type-materiel', 'autre', 'Autre', 1),
                ('etat-materiel', 'en_service', 'En service', 1),
                ('etat-materiel', 'en_stock', 'En stock', 1),
                ('etat-materiel', 'en_panne', 'En panne', 1),
                ('etat-materiel', 'en_maintenance', 'En maintenance', 1),
                ('etat-materiel', 'reforme', 'Réformé', 1),
                ('type-vehicule', 'berline', 'Berline', 1),
                ('type-vehicule', 'suv_4x4', 'SUV / 4x4', 1),
                ('type-vehicule', 'camionnette', 'Camionnette', 1),
                ('type-vehicule', 'camion', 'Camion', 1),
                ('type-vehicule', 'moto', 'Moto', 1),
                ('type-vehicule', 'bus', 'Bus', 1),
                ('type-vehicule', 'autre', 'Autre', 1),
                ('etat-vehicule', 'en_service', 'En service', 1),
                ('etat-vehicule', 'en_mission', 'En mission', 1),
                ('etat-vehicule', 'en_panne', 'En panne', 1),
                ('etat-vehicule', 'en_maintenance', 'En maintenance', 1),
                ('etat-vehicule', 'reforme', 'Réformé', 1),
                ('type-contrat', 'fonctionnaire', 'Fonctionnaire', 1),
                ('type-contrat', 'contractuel', 'Contractuel', 1),
                ('type-contrat', 'stagiaire', 'Stagiaire', 1),
                ('type-contrat', 'consultant', 'Consultant', 1)
            SQL);
        $this->addSql('ALTER TABLE materiel_informatique ADD type_id INT NOT NULL, ADD etat_id INT NOT NULL, DROP type, DROP etat');
        $this->addSql('ALTER TABLE materiel_informatique ADD CONSTRAINT FK_A0C8655BC54C8C93 FOREIGN KEY (type_id) REFERENCES liste_valeur (id)');
        $this->addSql('ALTER TABLE materiel_informatique ADD CONSTRAINT FK_A0C8655BD5E86FF FOREIGN KEY (etat_id) REFERENCES liste_valeur (id)');
        $this->addSql('CREATE INDEX IDX_A0C8655BC54C8C93 ON materiel_informatique (type_id)');
        $this->addSql('CREATE INDEX IDX_A0C8655BD5E86FF ON materiel_informatique (etat_id)');
        $this->addSql('ALTER TABLE personnel ADD type_contrat_id INT NOT NULL, DROP type_contrat');
        $this->addSql('ALTER TABLE personnel ADD CONSTRAINT FK_A6BCF3DE520D03A FOREIGN KEY (type_contrat_id) REFERENCES liste_valeur (id)');
        $this->addSql('CREATE INDEX IDX_A6BCF3DE520D03A ON personnel (type_contrat_id)');
        $this->addSql('ALTER TABLE service ADD direction_id INT NOT NULL');
        $this->addSql('ALTER TABLE service ADD CONSTRAINT FK_E19D9AD2AF73D997 FOREIGN KEY (direction_id) REFERENCES direction (id)');
        $this->addSql('CREATE INDEX IDX_E19D9AD2AF73D997 ON service (direction_id)');
        $this->addSql('ALTER TABLE vehicule ADD type_id INT NOT NULL, ADD etat_id INT NOT NULL, DROP type, DROP etat');
        $this->addSql('ALTER TABLE vehicule ADD CONSTRAINT FK_292FFF1DC54C8C93 FOREIGN KEY (type_id) REFERENCES liste_valeur (id)');
        $this->addSql('ALTER TABLE vehicule ADD CONSTRAINT FK_292FFF1DD5E86FF FOREIGN KEY (etat_id) REFERENCES liste_valeur (id)');
        $this->addSql('CREATE INDEX IDX_292FFF1DC54C8C93 ON vehicule (type_id)');
        $this->addSql('CREATE INDEX IDX_292FFF1DD5E86FF ON vehicule (etat_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE service DROP FOREIGN KEY FK_E19D9AD2AF73D997');
        $this->addSql('ALTER TABLE materiel_informatique DROP FOREIGN KEY FK_A0C8655BC54C8C93');
        $this->addSql('ALTER TABLE materiel_informatique DROP FOREIGN KEY FK_A0C8655BD5E86FF');
        $this->addSql('ALTER TABLE personnel DROP FOREIGN KEY FK_A6BCF3DE520D03A');
        $this->addSql('ALTER TABLE vehicule DROP FOREIGN KEY FK_292FFF1DC54C8C93');
        $this->addSql('ALTER TABLE vehicule DROP FOREIGN KEY FK_292FFF1DD5E86FF');
        $this->addSql('DROP TABLE direction');
        $this->addSql('DROP TABLE liste_valeur');

        $this->addSql('DROP INDEX IDX_A0C8655BC54C8C93 ON materiel_informatique');
        $this->addSql('DROP INDEX IDX_A0C8655BD5E86FF ON materiel_informatique');
        $this->addSql('ALTER TABLE materiel_informatique ADD type VARCHAR(30) NOT NULL, ADD etat VARCHAR(20) NOT NULL, DROP type_id, DROP etat_id');
        $this->addSql('DROP INDEX IDX_E19D9AD2AF73D997 ON service');
        $this->addSql('ALTER TABLE service DROP direction_id');
        $this->addSql('DROP INDEX IDX_292FFF1DC54C8C93 ON vehicule');
        $this->addSql('DROP INDEX IDX_292FFF1DD5E86FF ON vehicule');
        $this->addSql('ALTER TABLE vehicule ADD type VARCHAR(20) NOT NULL, ADD etat VARCHAR(20) NOT NULL, DROP type_id, DROP etat_id');
        $this->addSql('DROP INDEX IDX_A6BCF3DE520D03A ON personnel');
        $this->addSql('ALTER TABLE personnel ADD type_contrat VARCHAR(20) NOT NULL, DROP type_contrat_id');
    }
}
