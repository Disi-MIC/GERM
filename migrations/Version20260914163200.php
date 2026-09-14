<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260914163200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crée audit_log (A09 — journalisation d\'audit, socle minimal).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE audit_log (
                id INT AUTO_INCREMENT NOT NULL,
                utilisateur_id INT DEFAULT NULL,
                action VARCHAR(30) NOT NULL,
                utilisateur_email VARCHAR(180) DEFAULT NULL,
                entite VARCHAR(100) DEFAULT NULL,
                entite_id INT DEFAULT NULL,
                details LONGTEXT DEFAULT NULL,
                ip_address VARCHAR(45) NOT NULL,
                user_agent LONGTEXT DEFAULT NULL,
                resultat VARCHAR(10) NOT NULL,
                created_at DATETIME NOT NULL,
                INDEX IDX_AUDIT_ACTION_DATE (action, created_at),
                INDEX IDX_AUDIT_UTILISATEUR_EMAIL (utilisateur_email),
                INDEX IDX_AUDITLOG_UTILISATEUR (utilisateur_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            SQL);
        $this->addSql('ALTER TABLE audit_log ADD CONSTRAINT FK_AUDITLOG_UTILISATEUR FOREIGN KEY (utilisateur_id) REFERENCES `user` (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE audit_log DROP FOREIGN KEY FK_AUDITLOG_UTILISATEUR');
        $this->addSql('DROP TABLE audit_log');
    }
}
