<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260804203641 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE historique_affectation (id INT AUTO_INCREMENT NOT NULL, personnel_id INT NOT NULL, service_id INT NOT NULL, fonction VARCHAR(150) NOT NULL, grade VARCHAR(100) DEFAULT NULL, type_mouvement VARCHAR(20) NOT NULL, date_effet DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', numero_decision VARCHAR(100) DEFAULT NULL, date_decision DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', observations LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_2D736FB51C109075 (personnel_id), INDEX IDX_2D736FB5ED5CA9E6 (service_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE historique_affectation ADD CONSTRAINT FK_2D736FB51C109075 FOREIGN KEY (personnel_id) REFERENCES personnel (id)');
        $this->addSql('ALTER TABLE historique_affectation ADD CONSTRAINT FK_2D736FB5ED5CA9E6 FOREIGN KEY (service_id) REFERENCES service (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE historique_affectation DROP FOREIGN KEY FK_2D736FB51C109075');
        $this->addSql('ALTER TABLE historique_affectation DROP FOREIGN KEY FK_2D736FB5ED5CA9E6');
        $this->addSql('DROP TABLE historique_affectation');
    }
}
