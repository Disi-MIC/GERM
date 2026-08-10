<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260810173718 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE document_administratif (id INT AUTO_INCREMENT NOT NULL, personnel_id INT NOT NULL, type_id INT NOT NULL, libelle VARCHAR(150) NOT NULL, date_document DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', date_expiration DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', chemin_fichier VARCHAR(255) NOT NULL, nom_original VARCHAR(255) NOT NULL, observations LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_33F0D1211C109075 (personnel_id), INDEX IDX_33F0D121C54C8C93 (type_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE document_administratif ADD CONSTRAINT FK_33F0D1211C109075 FOREIGN KEY (personnel_id) REFERENCES personnel (id)');
        $this->addSql('ALTER TABLE document_administratif ADD CONSTRAINT FK_33F0D121C54C8C93 FOREIGN KEY (type_id) REFERENCES liste_valeur (id)');
        $this->addSql(<<<'SQL'
            INSERT INTO liste_valeur (categorie, code, libelle, actif) VALUES
                ('type-document', 'cni', 'Carte nationale d\'identité', 1),
                ('type-document', 'passeport', 'Passeport', 1),
                ('type-document', 'acte_naissance', 'Acte de naissance', 1),
                ('type-document', 'diplome', 'Diplôme', 1),
                ('type-document', 'contrat', 'Contrat de travail', 1),
                ('type-document', 'decision_nomination', 'Décision de nomination', 1),
                ('type-document', 'certificat_medical', 'Certificat médical', 1),
                ('type-document', 'casier_judiciaire', 'Casier judiciaire', 1),
                ('type-document', 'attestation', 'Attestation', 1),
                ('type-document', 'cv', 'Curriculum vitae', 1),
                ('type-document', 'autre', 'Autre', 1)
            SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("DELETE FROM liste_valeur WHERE categorie = 'type-document'");
        $this->addSql('ALTER TABLE document_administratif DROP FOREIGN KEY FK_33F0D1211C109075');
        $this->addSql('ALTER TABLE document_administratif DROP FOREIGN KEY FK_33F0D121C54C8C93');
        $this->addSql('DROP TABLE document_administratif');
    }
}
