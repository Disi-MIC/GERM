<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajoute au Personnel les champs demandés par la fiche RH ministérielle
 * (Prog/Chapitre/CNI/Corps/Hiérarchie) : cni (texte, unique) et quatre
 * listes paramétrables (corps, hiérarchie, programme, chapitre budgétaires)
 * référençant liste_valeur, même mécanisme que typeContrat.
 *
 * Écrite à la main plutôt que via doctrine:migrations:diff : la base locale
 * de développement porte une dérive antérieure et non suivie en migrations
 * (tables bon_essence/historique_vidange, colonnes de vidange sur vehicule —
 * une migration appliquée localement le 31/08/2026 dont le fichier a depuis
 * disparu du dépôt), que le diff automatique aurait proposé de supprimer.
 * Cette migration ne touche donc que ce que ce commit a réellement changé.
 */
final class Version20260921100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute personnel.cni et les listes corps/hierarchie/programme/chapitre';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE personnel ADD cni VARCHAR(20) DEFAULT NULL, ADD corps_id INT DEFAULT NULL, ADD hierarchie_id INT DEFAULT NULL, ADD programme_id INT DEFAULT NULL, ADD chapitre_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE personnel ADD CONSTRAINT FK_A6BCF3DE190A1B68 FOREIGN KEY (corps_id) REFERENCES liste_valeur (id)');
        $this->addSql('ALTER TABLE personnel ADD CONSTRAINT FK_A6BCF3DEC70EA09D FOREIGN KEY (hierarchie_id) REFERENCES liste_valeur (id)');
        $this->addSql('ALTER TABLE personnel ADD CONSTRAINT FK_A6BCF3DE62BB7AEE FOREIGN KEY (programme_id) REFERENCES liste_valeur (id)');
        $this->addSql('ALTER TABLE personnel ADD CONSTRAINT FK_A6BCF3DE1FBEEF7B FOREIGN KEY (chapitre_id) REFERENCES liste_valeur (id)');
        $this->addSql('CREATE INDEX IDX_A6BCF3DE190A1B68 ON personnel (corps_id)');
        $this->addSql('CREATE INDEX IDX_A6BCF3DEC70EA09D ON personnel (hierarchie_id)');
        $this->addSql('CREATE INDEX IDX_A6BCF3DE62BB7AEE ON personnel (programme_id)');
        $this->addSql('CREATE INDEX IDX_A6BCF3DE1FBEEF7B ON personnel (chapitre_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_PERSONNEL_CNI ON personnel (cni)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE personnel DROP FOREIGN KEY FK_A6BCF3DE190A1B68');
        $this->addSql('ALTER TABLE personnel DROP FOREIGN KEY FK_A6BCF3DEC70EA09D');
        $this->addSql('ALTER TABLE personnel DROP FOREIGN KEY FK_A6BCF3DE62BB7AEE');
        $this->addSql('ALTER TABLE personnel DROP FOREIGN KEY FK_A6BCF3DE1FBEEF7B');
        $this->addSql('DROP INDEX IDX_A6BCF3DE190A1B68 ON personnel');
        $this->addSql('DROP INDEX IDX_A6BCF3DEC70EA09D ON personnel');
        $this->addSql('DROP INDEX IDX_A6BCF3DE62BB7AEE ON personnel');
        $this->addSql('DROP INDEX IDX_A6BCF3DE1FBEEF7B ON personnel');
        $this->addSql('DROP INDEX UNIQ_PERSONNEL_CNI ON personnel');
        $this->addSql('ALTER TABLE personnel DROP cni, DROP corps_id, DROP hierarchie_id, DROP programme_id, DROP chapitre_id');
    }
}
