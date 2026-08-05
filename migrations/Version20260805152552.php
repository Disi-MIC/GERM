<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260805152552 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE demande_carte_pro (id INT AUTO_INCREMENT NOT NULL, personnel_id INT NOT NULL, carte_reference_id INT DEFAULT NULL, carte_creee_id INT DEFAULT NULL, type_demande VARCHAR(20) NOT NULL, motif LONGTEXT DEFAULT NULL, statut VARCHAR(20) NOT NULL, date_traitement DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', commentaire_traitement LONGTEXT DEFAULT NULL, chemin_fichier VARCHAR(255) DEFAULT NULL, nom_original VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_578795CE1C109075 (personnel_id), INDEX IDX_578795CE1E77441 (carte_reference_id), INDEX IDX_578795CE574DB4FA (carte_creee_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE demande_carte_pro ADD CONSTRAINT FK_578795CE1C109075 FOREIGN KEY (personnel_id) REFERENCES personnel (id)');
        $this->addSql('ALTER TABLE demande_carte_pro ADD CONSTRAINT FK_578795CE1E77441 FOREIGN KEY (carte_reference_id) REFERENCES carte_professionnelle (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE demande_carte_pro ADD CONSTRAINT FK_578795CE574DB4FA FOREIGN KEY (carte_creee_id) REFERENCES carte_professionnelle (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE demande_carte_pro DROP FOREIGN KEY FK_578795CE1C109075');
        $this->addSql('ALTER TABLE demande_carte_pro DROP FOREIGN KEY FK_578795CE1E77441');
        $this->addSql('ALTER TABLE demande_carte_pro DROP FOREIGN KEY FK_578795CE574DB4FA');
        $this->addSql('DROP TABLE demande_carte_pro');
    }
}
