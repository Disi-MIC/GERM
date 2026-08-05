<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260805150112 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE carte_professionnelle (id INT AUTO_INCREMENT NOT NULL, personnel_id INT NOT NULL, numero VARCHAR(100) NOT NULL, date_delivrance DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', date_expiration DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', statut VARCHAR(20) NOT NULL, observations LONGTEXT DEFAULT NULL, chemin_fichier VARCHAR(255) DEFAULT NULL, nom_original VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_157320EA1C109075 (personnel_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE carte_professionnelle ADD CONSTRAINT FK_157320EA1C109075 FOREIGN KEY (personnel_id) REFERENCES personnel (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE carte_professionnelle DROP FOREIGN KEY FK_157320EA1C109075');
        $this->addSql('DROP TABLE carte_professionnelle');
    }
}
