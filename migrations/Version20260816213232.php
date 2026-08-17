<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260816213232 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Ajoute document_administratif.soumis_par_agent (repère RH : document déposé par l'agent lui-même vs. archivé par le RH Personnel).";
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE document_administratif ADD soumis_par_agent TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE document_administratif DROP soumis_par_agent');
    }
}
