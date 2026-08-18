<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260818113912 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Circuit demande de décision revu : retire la validation RH Admin sur DecisionConge (le circuit ministériel se déroule maintenant entièrement hors application avant que le RH Admin ne dépose les documents scannés du retour), ajoute demande_decision.chemin/nom_original_document_retour pour ce dépôt.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE decision_conge DROP FOREIGN KEY FK_99D5441629A7BB2');
        $this->addSql('DROP INDEX IDX_99D5441629A7BB2 ON decision_conge');
        $this->addSql('ALTER TABLE decision_conge DROP validee_par_id, DROP validee_par_admin_rh, DROP validee_le');
        $this->addSql('ALTER TABLE demande_decision ADD chemin_document_retour VARCHAR(255) DEFAULT NULL, ADD nom_original_document_retour VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE
              decision_conge
            ADD
              validee_par_id INT DEFAULT NULL,
            ADD
              validee_par_admin_rh TINYINT(1) DEFAULT 1 NOT NULL,
            ADD
              validee_le DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              decision_conge
            ADD
              CONSTRAINT FK_99D5441629A7BB2 FOREIGN KEY (validee_par_id) REFERENCES user (id) ON
            UPDATE
              NO ACTION ON DELETE NO ACTION
        SQL);
        $this->addSql('CREATE INDEX IDX_99D5441629A7BB2 ON decision_conge (validee_par_id)');
        $this->addSql('ALTER TABLE demande_decision DROP chemin_document_retour, DROP nom_original_document_retour');
    }
}
