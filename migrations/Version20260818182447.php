<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260818182447 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute Service.responsable et Direction.directeur (chef de service / directeur, pour les aperçus organisationnels par rôle).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE direction ADD directeur_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE direction ADD CONSTRAINT FK_3E4AD1B3E82E7EE8 FOREIGN KEY (directeur_id) REFERENCES personnel (id)');
        $this->addSql('CREATE INDEX IDX_3E4AD1B3E82E7EE8 ON direction (directeur_id)');
        $this->addSql('ALTER TABLE service ADD responsable_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE service ADD CONSTRAINT FK_E19D9AD253C59D72 FOREIGN KEY (responsable_id) REFERENCES personnel (id)');
        $this->addSql('CREATE INDEX IDX_E19D9AD253C59D72 ON service (responsable_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE direction DROP FOREIGN KEY FK_3E4AD1B3E82E7EE8');
        $this->addSql('DROP INDEX IDX_3E4AD1B3E82E7EE8 ON direction');
        $this->addSql('ALTER TABLE direction DROP directeur_id');
        $this->addSql('ALTER TABLE service DROP FOREIGN KEY FK_E19D9AD253C59D72');
        $this->addSql('DROP INDEX IDX_E19D9AD253C59D72 ON service');
        $this->addSql('ALTER TABLE service DROP responsable_id');
    }
}
