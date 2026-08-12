<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260811150930 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convert TicketIncident.priorite and Maintenance.type from hardcoded enums to paramétrable ListeValeur relations';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            INSERT INTO liste_valeur (categorie, code, libelle, actif) VALUES
                ('priorite-ticket', 'basse', 'Basse', 1),
                ('priorite-ticket', 'normale', 'Normale', 1),
                ('priorite-ticket', 'haute', 'Haute', 1),
                ('priorite-ticket', 'critique', 'Critique', 1),
                ('type-maintenance', 'preventive', 'Préventive', 1),
                ('type-maintenance', 'corrective', 'Corrective', 1)
            SQL);
        // ticket_incident/maintenance sont vides à ce stade (module tout juste
        // livré) : conversion directe des colonnes enum en FK NOT NULL, sans
        // backfill de données existantes.
        $this->addSql('ALTER TABLE maintenance ADD type_id INT NOT NULL, DROP type');
        $this->addSql('ALTER TABLE maintenance ADD CONSTRAINT FK_2F84F8E9C54C8C93 FOREIGN KEY (type_id) REFERENCES liste_valeur (id)');
        $this->addSql('CREATE INDEX IDX_2F84F8E9C54C8C93 ON maintenance (type_id)');
        $this->addSql('ALTER TABLE ticket_incident ADD priorite_id INT NOT NULL, DROP priorite');
        $this->addSql('ALTER TABLE ticket_incident ADD CONSTRAINT FK_B86A5D9B53B4F1DE FOREIGN KEY (priorite_id) REFERENCES liste_valeur (id)');
        $this->addSql('CREATE INDEX IDX_B86A5D9B53B4F1DE ON ticket_incident (priorite_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE maintenance DROP FOREIGN KEY FK_2F84F8E9C54C8C93');
        $this->addSql('DROP INDEX IDX_2F84F8E9C54C8C93 ON maintenance');
        $this->addSql('ALTER TABLE maintenance ADD type VARCHAR(20) NOT NULL, DROP type_id');
        $this->addSql('ALTER TABLE ticket_incident DROP FOREIGN KEY FK_B86A5D9B53B4F1DE');
        $this->addSql('DROP INDEX IDX_B86A5D9B53B4F1DE ON ticket_incident');
        $this->addSql('ALTER TABLE ticket_incident ADD priorite VARCHAR(20) NOT NULL, DROP priorite_id');
        $this->addSql("DELETE FROM liste_valeur WHERE categorie IN ('priorite-ticket', 'type-maintenance')");
    }
}
