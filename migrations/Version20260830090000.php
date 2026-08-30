<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260830090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crée historique_changement_personnel — journal des changements directs (nom, prénom, matricule, statut, fonction, grade, type de contrat) sur une fiche Personnel.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE historique_changement_personnel (
                id INT AUTO_INCREMENT NOT NULL,
                personnel_id INT NOT NULL,
                enregistre_par_id INT DEFAULT NULL,
                champ VARCHAR(100) NOT NULL,
                valeur_avant VARCHAR(255) DEFAULT NULL,
                valeur_apres VARCHAR(255) DEFAULT NULL,
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                INDEX IDX_D593C3751C109075 (personnel_id),
                INDEX IDX_D593C375CB5FDB3E (enregistre_par_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            SQL);
        $this->addSql('ALTER TABLE historique_changement_personnel ADD CONSTRAINT FK_HISTCHGPERS_PERSONNEL FOREIGN KEY (personnel_id) REFERENCES personnel (id)');
        $this->addSql('ALTER TABLE historique_changement_personnel ADD CONSTRAINT FK_HISTCHGPERS_ENREGISTRE_PAR FOREIGN KEY (enregistre_par_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE historique_changement_personnel DROP FOREIGN KEY FK_HISTCHGPERS_PERSONNEL');
        $this->addSql('ALTER TABLE historique_changement_personnel DROP FOREIGN KEY FK_HISTCHGPERS_ENREGISTRE_PAR');
        $this->addSql('DROP TABLE historique_changement_personnel');
    }
}
