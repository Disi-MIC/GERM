<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260813160353 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'materiel_informatique.service_id ne sert plus qu\'au matériel non affecté (MaterielInformatique::getService() délègue à l\'agent affecté dès qu\'il est renseigné) — supprime la copie devenue redondante pour le matériel déjà affecté.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE materiel_informatique CHANGE service_id service_id INT DEFAULT NULL');

        // Un matériel affecté n'a plus sa propre copie de service : elle se
        // déduit désormais de l'agent (getService()), donc la valeur stockée
        // ici ne serait plus jamais lue et ne ferait que risquer de diverger
        // si on la laissait (ex. l'agent change de service).
        $this->addSql('UPDATE materiel_informatique SET service_id = NULL WHERE affecte_a_id IS NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // Restaure une copie sur le matériel affecté avant de remettre NOT
        // NULL, sans quoi ces lignes (mises à NULL par le up()) feraient
        // échouer l'ALTER. Un matériel non affecté et sans service (en stock
        // ou réformé, autorisé depuis cette version) n'a rien à restaurer :
        // nécessiterait un choix arbitraire de service, laissé à une
        // intervention manuelle si ce rollback est joué après coup.
        $this->addSql(<<<'SQL'
            UPDATE materiel_informatique m
            INNER JOIN personnel p ON p.id = m.affecte_a_id
            SET m.service_id = p.service_id
            WHERE m.service_id IS NULL
        SQL);

        $this->addSql('ALTER TABLE materiel_informatique CHANGE service_id service_id INT NOT NULL');
    }
}
