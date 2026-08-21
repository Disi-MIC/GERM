<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260821162817 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Passe parametres_decision_conge d'une ligne unique à une ligne par catégorie (fonctionnaire/non-fonctionnaire) — table vidée au passage (aucun texte légal encore saisi en prod à ce jour), les deux catégories se recréent vides au premier accès (voir ParametresDecisionCongeRepository::recupererOuCreer()).";
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DELETE FROM parametres_decision_conge');
        $this->addSql('ALTER TABLE parametres_decision_conge MODIFY id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE parametres_decision_conge ADD categorie VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE parametres_decision_conge ADD UNIQUE INDEX uniq_parametres_decision_categorie (categorie)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE parametres_decision_conge DROP INDEX uniq_parametres_decision_categorie');
        $this->addSql('ALTER TABLE parametres_decision_conge DROP categorie');
        $this->addSql('ALTER TABLE parametres_decision_conge MODIFY id INT NOT NULL');
        $this->addSql('DELETE FROM parametres_decision_conge');
    }
}
