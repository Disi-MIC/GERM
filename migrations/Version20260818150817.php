<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260818150817 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Ajoute decision_conge.numero_derniere_decision_referencee, pour le visa de la décision antérieure dans le préambule du document.";
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE decision_conge ADD numero_derniere_decision_referencee VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE decision_conge DROP numero_derniere_decision_referencee');
    }
}
