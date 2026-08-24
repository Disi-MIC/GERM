<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260822010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Crée changement_cartouche — journal des changements de cartouche/toner par imprimante (App\\Entity\\ChangementCartouche), sur le même modèle que maintenance : pas de circuit de demande, l'IT consigne directement au moment du changement.";
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE changement_cartouche (
                id INT AUTO_INCREMENT NOT NULL,
                materiel_id INT NOT NULL,
                enregistre_par_id INT DEFAULT NULL,
                couleur VARCHAR(20) NOT NULL,
                reference VARCHAR(100) DEFAULT NULL,
                date_changement DATE NOT NULL,
                observations LONGTEXT DEFAULT NULL,
                created_at DATETIME NOT NULL,
                INDEX IDX_8275096F16880AAF (materiel_id),
                INDEX IDX_8275096FCB5FDB3E (enregistre_par_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            SQL);
        $this->addSql('ALTER TABLE changement_cartouche ADD CONSTRAINT FK_8275096F16880AAF FOREIGN KEY (materiel_id) REFERENCES materiel_informatique (id)');
        $this->addSql('ALTER TABLE changement_cartouche ADD CONSTRAINT FK_8275096FCB5FDB3E FOREIGN KEY (enregistre_par_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE changement_cartouche DROP FOREIGN KEY FK_8275096F16880AAF');
        $this->addSql('ALTER TABLE changement_cartouche DROP FOREIGN KEY FK_8275096FCB5FDB3E');
        $this->addSql('DROP TABLE changement_cartouche');
    }
}
