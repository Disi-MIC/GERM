<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajoute MaterielInformatique.niveauVulnerabilite (liste de valeurs,
 * catégorie CategorieListeValeur::NIVEAU_VULNERABILITE) — pour l'aperçu du
 * Ministère (répartition du parc informatique par niveau de vulnérabilité).
 */
final class Version20260819102123 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute MaterielInformatique.niveauVulnerabilite';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE materiel_informatique ADD niveau_vulnerabilite_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE materiel_informatique ADD CONSTRAINT FK_A0C8655B37AC521B FOREIGN KEY (niveau_vulnerabilite_id) REFERENCES liste_valeur (id)');
        $this->addSql('CREATE INDEX IDX_A0C8655B37AC521B ON materiel_informatique (niveau_vulnerabilite_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE materiel_informatique DROP FOREIGN KEY FK_A0C8655B37AC521B');
        $this->addSql('DROP INDEX IDX_A0C8655B37AC521B ON materiel_informatique');
        $this->addSql('ALTER TABLE materiel_informatique DROP niveau_vulnerabilite_id');
    }
}
