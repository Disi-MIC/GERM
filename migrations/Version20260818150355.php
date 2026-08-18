<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260818150355 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Ajoute decision_conge.ampliations/numero_attestation_non_jouissance/date_attestation_non_jouissance et parametres_decision_conge.ampliations, pour rapprocher le document généré du modèle papier réel.";
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE decision_conge
              ADD ampliations LONGTEXT DEFAULT NULL,
              ADD numero_attestation_non_jouissance VARCHAR(100) DEFAULT NULL,
              ADD date_attestation_non_jouissance DATE DEFAULT NULL COMMENT '(DC2Type:date_immutable)'
        SQL);
        $this->addSql('ALTER TABLE parametres_decision_conge ADD ampliations LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE decision_conge DROP ampliations, DROP numero_attestation_non_jouissance, DROP date_attestation_non_jouissance');
        $this->addSql('ALTER TABLE parametres_decision_conge DROP ampliations');
    }
}
