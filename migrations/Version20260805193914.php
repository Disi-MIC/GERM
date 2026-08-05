<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260805193914 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE delegation (id INT AUTO_INCREMENT NOT NULL, delegant_id INT NOT NULL, delegataire_id INT NOT NULL, role_delegue VARCHAR(30) NOT NULL, date_debut DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', date_fin DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', motif LONGTEXT DEFAULT NULL, statut VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_292F436D48FE0B5C (delegant_id), INDEX IDX_292F436D1F24AFB0 (delegataire_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE delegation ADD CONSTRAINT FK_292F436D48FE0B5C FOREIGN KEY (delegant_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE delegation ADD CONSTRAINT FK_292F436D1F24AFB0 FOREIGN KEY (delegataire_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE delegation DROP FOREIGN KEY FK_292F436D48FE0B5C');
        $this->addSql('ALTER TABLE delegation DROP FOREIGN KEY FK_292F436D1F24AFB0');
        $this->addSql('DROP TABLE delegation');
    }
}
