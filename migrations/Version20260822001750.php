<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260822001750 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute demande_decision.numero_prise_de_service — accompagne date_prise_de_service pour un agent nouvellement affecté, référencé dans le texte légal de la décision générée (voir TexteLegalDecisionCongeSubstitutionService).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE demande_decision ADD numero_prise_de_service VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE demande_decision DROP numero_prise_de_service');
    }
}
