<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260817204611 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Circuit à 4 étapes pour DemandeDecision (RH Congé génère+transmet, RH Admin valide, RH Congé confirme la remise) : DecisionConge.nombre_jours/generee_par/validee_par_admin_rh/validee_par/validee_le, DemandeDecision.motif_rejet.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE
              decision_conge
            ADD
              generee_par_id INT DEFAULT NULL,
            ADD
              validee_par_id INT DEFAULT NULL,
            ADD
              nombre_jours INT DEFAULT NULL,
            ADD
              validee_par_admin_rh TINYINT(1) DEFAULT 1 NOT NULL,
            ADD
              validee_le DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              decision_conge
            ADD
              CONSTRAINT FK_99D5441950F31C9 FOREIGN KEY (generee_par_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              decision_conge
            ADD
              CONSTRAINT FK_99D5441629A7BB2 FOREIGN KEY (validee_par_id) REFERENCES `user` (id)
        SQL);
        $this->addSql('CREATE INDEX IDX_99D5441950F31C9 ON decision_conge (generee_par_id)');
        $this->addSql('CREATE INDEX IDX_99D5441629A7BB2 ON decision_conge (validee_par_id)');
        $this->addSql('ALTER TABLE demande_decision ADD motif_rejet_id INT DEFAULT NULL');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              demande_decision
            ADD
              CONSTRAINT FK_C2F5F78ACC36B3F FOREIGN KEY (motif_rejet_id) REFERENCES liste_valeur (id)
        SQL);
        $this->addSql('CREATE INDEX IDX_C2F5F78ACC36B3F ON demande_decision (motif_rejet_id)');

        $this->addSql(<<<'SQL'
            INSERT INTO liste_valeur (categorie, code, libelle, actif) VALUES
                ('motif-rejet-decision-conge', 'piece_manquante', 'Pièce manquante', 1),
                ('motif-rejet-decision-conge', 'piece_illisible', 'Pièce illisible ou non conforme', 1),
                ('motif-rejet-decision-conge', 'agent_non_eligible', 'Agent non éligible', 1),
                ('motif-rejet-decision-conge', 'informations_incorrectes', 'Informations incorrectes', 1),
                ('motif-rejet-decision-conge', 'autre', 'Autre', 1)
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM liste_valeur WHERE categorie = 'motif-rejet-decision-conge'");

        $this->addSql('ALTER TABLE decision_conge DROP FOREIGN KEY FK_99D5441950F31C9');
        $this->addSql('ALTER TABLE decision_conge DROP FOREIGN KEY FK_99D5441629A7BB2');
        $this->addSql('DROP INDEX IDX_99D5441950F31C9 ON decision_conge');
        $this->addSql('DROP INDEX IDX_99D5441629A7BB2 ON decision_conge');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              decision_conge
            DROP
              generee_par_id,
            DROP
              validee_par_id,
            DROP
              nombre_jours,
            DROP
              validee_par_admin_rh,
            DROP
              validee_le
        SQL);
        $this->addSql('ALTER TABLE demande_decision DROP FOREIGN KEY FK_C2F5F78ACC36B3F');
        $this->addSql('DROP INDEX IDX_C2F5F78ACC36B3F ON demande_decision');
        $this->addSql('ALTER TABLE demande_decision DROP motif_rejet_id');
    }
}
