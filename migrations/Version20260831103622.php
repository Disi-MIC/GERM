<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260831103622 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crée historique_vidange et bon_essence (journaux du parc automobile) et ajoute le suivi de vidange à Vehicule.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE historique_vidange (
                id INT AUTO_INCREMENT NOT NULL,
                vehicule_id INT NOT NULL,
                date DATE NOT NULL COMMENT '(DC2Type:date_immutable)',
                kilometrage INT NOT NULL,
                cout NUMERIC(10, 2) DEFAULT NULL,
                prestataire VARCHAR(150) DEFAULT NULL,
                observations LONGTEXT DEFAULT NULL,
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                INDEX IDX_E53EF4F44A4A3511 (vehicule_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            SQL);
        $this->addSql('ALTER TABLE historique_vidange ADD CONSTRAINT FK_E53EF4F44A4A3511 FOREIGN KEY (vehicule_id) REFERENCES vehicule (id)');

        $this->addSql(<<<'SQL'
            CREATE TABLE bon_essence (
                id INT AUTO_INCREMENT NOT NULL,
                vehicule_id INT NOT NULL,
                chauffeur_id INT DEFAULT NULL,
                numero VARCHAR(50) DEFAULT NULL,
                date DATE NOT NULL COMMENT '(DC2Type:date_immutable)',
                quantite_litres NUMERIC(8, 2) DEFAULT NULL,
                montant NUMERIC(10, 2) DEFAULT NULL,
                kilometrage_releve INT DEFAULT NULL,
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                INDEX IDX_8C87B02E4A4A3511 (vehicule_id),
                INDEX IDX_8C87B02E85C0B3BE (chauffeur_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            SQL);
        $this->addSql('ALTER TABLE bon_essence ADD CONSTRAINT FK_8C87B02E4A4A3511 FOREIGN KEY (vehicule_id) REFERENCES vehicule (id)');
        $this->addSql('ALTER TABLE bon_essence ADD CONSTRAINT FK_8C87B02E85C0B3BE FOREIGN KEY (chauffeur_id) REFERENCES personnel (id)');

        $this->addSql(<<<'SQL'
            ALTER TABLE vehicule
                ADD periodicite_vidange_km INT DEFAULT NULL,
                ADD derniere_vidange_km INT DEFAULT NULL,
                ADD derniere_vidange_date DATE DEFAULT NULL COMMENT '(DC2Type:date_immutable)'
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE historique_vidange DROP FOREIGN KEY FK_E53EF4F44A4A3511');
        $this->addSql('ALTER TABLE bon_essence DROP FOREIGN KEY FK_8C87B02E4A4A3511');
        $this->addSql('ALTER TABLE bon_essence DROP FOREIGN KEY FK_8C87B02E85C0B3BE');
        $this->addSql('DROP TABLE historique_vidange');
        $this->addSql('DROP TABLE bon_essence');
        $this->addSql('ALTER TABLE vehicule DROP periodicite_vidange_km, DROP derniere_vidange_km, DROP derniere_vidange_date');
    }
}
