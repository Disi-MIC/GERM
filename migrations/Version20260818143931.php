<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260818143931 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute parametres_decision_conge (texte légal par défaut RH Admin) et les colonnes de copie sur decision_conge (periode_debut, visas_decrets, article2, article3).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE parametres_decision_conge (
              id INT NOT NULL,
              mis_ajour_par_id INT DEFAULT NULL,
              visas_decrets LONGTEXT DEFAULT NULL,
              article2 LONGTEXT DEFAULT NULL,
              article3 LONGTEXT DEFAULT NULL,
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              INDEX IDX_BC782BC27F2BDDB (mis_ajour_par_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql('ALTER TABLE parametres_decision_conge ADD CONSTRAINT FK_BC782BC27F2BDDB FOREIGN KEY (mis_ajour_par_id) REFERENCES `user` (id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE decision_conge
              ADD periode_debut DATE DEFAULT NULL COMMENT '(DC2Type:date_immutable)',
              ADD visas_decrets LONGTEXT DEFAULT NULL,
              ADD article2 LONGTEXT DEFAULT NULL,
              ADD article3 LONGTEXT DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE parametres_decision_conge DROP FOREIGN KEY FK_BC782BC27F2BDDB');
        $this->addSql('DROP TABLE parametres_decision_conge');
        $this->addSql('ALTER TABLE decision_conge DROP periode_debut, DROP visas_decrets, DROP article2, DROP article3');
    }
}
