<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260902150637 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crée composant_materiel (RAM, disque dur HDD/SSD, carte graphique...) et seed la liste de valeurs type-composant.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE composant_materiel (id INT AUTO_INCREMENT NOT NULL, specification VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, materiel_id INT NOT NULL, type_id INT NOT NULL, INDEX IDX_AAFDD9A716880AAF (materiel_id), INDEX IDX_AAFDD9A7C54C8C93 (type_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE composant_materiel ADD CONSTRAINT FK_AAFDD9A716880AAF FOREIGN KEY (materiel_id) REFERENCES materiel_informatique (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE composant_materiel ADD CONSTRAINT FK_AAFDD9A7C54C8C93 FOREIGN KEY (type_id) REFERENCES liste_valeur (id)');

        $this->addSql(<<<'SQL'
            INSERT INTO liste_valeur (categorie, code, libelle, actif) VALUES
                ('type-composant', 'ram', 'RAM', 1),
                ('type-composant', 'disque_dur', 'Disque dur (HDD/SSD)', 1),
                ('type-composant', 'processeur', 'Processeur', 1),
                ('type-composant', 'carte_mere', 'Carte mère', 1),
                ('type-composant', 'carte_graphique', 'Carte graphique', 1),
                ('type-composant', 'alimentation', 'Alimentation', 1),
                ('type-composant', 'ecran', 'Écran', 1),
                ('type-composant', 'batterie', 'Batterie', 1),
                ('type-composant', 'autre', 'Autre', 1)
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM liste_valeur WHERE categorie = 'type-composant'");
        $this->addSql('ALTER TABLE composant_materiel DROP FOREIGN KEY FK_AAFDD9A716880AAF');
        $this->addSql('ALTER TABLE composant_materiel DROP FOREIGN KEY FK_AAFDD9A7C54C8C93');
        $this->addSql('DROP TABLE composant_materiel');
    }
}
