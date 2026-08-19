<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Justificatif (note de service) de la nomination d'un responsable de
 * service / directeur de direction : numéro, date, fichier scanné facultatif.
 */
final class Version20260819132439 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la note de service (numéro/date/fichier) sur Direction et Service';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE direction ADD note_service_numero VARCHAR(100) DEFAULT NULL, ADD note_service_date DATE DEFAULT NULL, ADD note_service_fichier VARCHAR(255) DEFAULT NULL, ADD note_service_nom_original VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE service ADD note_service_numero VARCHAR(100) DEFAULT NULL, ADD note_service_date DATE DEFAULT NULL, ADD note_service_fichier VARCHAR(255) DEFAULT NULL, ADD note_service_nom_original VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE direction DROP note_service_numero, DROP note_service_date, DROP note_service_fichier, DROP note_service_nom_original');
        $this->addSql('ALTER TABLE service DROP note_service_numero, DROP note_service_date, DROP note_service_fichier, DROP note_service_nom_original');
    }
}
