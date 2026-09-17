<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260917153000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la pièce justificative facultative (chemin_fichier, nom_original) sur maintenance.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE maintenance ADD chemin_fichier VARCHAR(255) DEFAULT NULL, ADD nom_original VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE maintenance DROP chemin_fichier, DROP nom_original');
    }
}
