<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260821161603 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute parametre_eligibilite_conge (jours acquis par mois, plafond, délai d\'éligibilité — un jeu de paramètres par catégorie fonctionnaire/non-fonctionnaire, réglable par le RH Admin).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE parametre_eligibilite_conge (
              id INT AUTO_INCREMENT NOT NULL,
              categorie VARCHAR(20) NOT NULL,
              jours_par_mois INT NOT NULL,
              plafond_jours INT NOT NULL,
              delai_eligibilite_mois INT NOT NULL,
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              mis_ajour_par_id INT DEFAULT NULL,
              INDEX IDX_8CCABE997F2BDDB (mis_ajour_par_id),
              UNIQUE INDEX uniq_parametre_eligibilite_categorie (categorie),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql('ALTER TABLE parametre_eligibilite_conge ADD CONSTRAINT FK_8CCABE997F2BDDB FOREIGN KEY (mis_ajour_par_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE parametre_eligibilite_conge DROP FOREIGN KEY FK_8CCABE997F2BDDB');
        $this->addSql('DROP TABLE parametre_eligibilite_conge');
    }
}
