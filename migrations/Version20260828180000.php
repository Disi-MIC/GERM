<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260828180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crée historique_changement_materiel — journal des changements d\'état, de service et de licence installée sur un matériel informatique.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE historique_changement_materiel (
                id INT AUTO_INCREMENT NOT NULL,
                materiel_id INT NOT NULL,
                enregistre_par_id INT DEFAULT NULL,
                champ VARCHAR(100) NOT NULL,
                valeur_avant VARCHAR(255) DEFAULT NULL,
                valeur_apres VARCHAR(255) DEFAULT NULL,
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                INDEX IDX_EBF4981616880AAF (materiel_id),
                INDEX IDX_EBF49816CB5FDB3E (enregistre_par_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            SQL);
        $this->addSql('ALTER TABLE historique_changement_materiel ADD CONSTRAINT FK_EBF4981616880AAF FOREIGN KEY (materiel_id) REFERENCES materiel_informatique (id)');
        $this->addSql('ALTER TABLE historique_changement_materiel ADD CONSTRAINT FK_EBF49816CB5FDB3E FOREIGN KEY (enregistre_par_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE historique_changement_materiel DROP FOREIGN KEY FK_EBF4981616880AAF');
        $this->addSql('ALTER TABLE historique_changement_materiel DROP FOREIGN KEY FK_EBF49816CB5FDB3E');
        $this->addSql('DROP TABLE historique_changement_materiel');
    }
}
