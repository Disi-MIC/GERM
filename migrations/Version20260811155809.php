<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260811155809 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create licence_logiciel table (software license registry: Kaspersky, Office...)';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE licence_logiciel (id INT AUTO_INCREMENT NOT NULL, logiciel_id INT NOT NULL, numero_licence VARCHAR(150) DEFAULT NULL, nombre_postes INT DEFAULT NULL, date_debut DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', date_expiration DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', fournisseur VARCHAR(150) DEFAULT NULL, cout NUMERIC(12, 2) DEFAULT NULL, observations LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_7EF63360CA84195D (logiciel_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE licence_logiciel ADD CONSTRAINT FK_7EF63360CA84195D FOREIGN KEY (logiciel_id) REFERENCES liste_valeur (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE licence_logiciel DROP FOREIGN KEY FK_7EF63360CA84195D');
        $this->addSql('DROP TABLE licence_logiciel');
    }
}
