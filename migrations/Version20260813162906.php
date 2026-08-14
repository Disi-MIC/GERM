<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260813162906 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Renomme materiel_informatique.garantie_jusquau en date_mise_en_service : jamais renseignée en pratique (0 des 39 matériels réels en prod), remplacée par une date pertinente même pour un matériel antérieur à la plateforme.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE
              materiel_informatique
            CHANGE
              garantie_jusquau date_mise_en_service DATE DEFAULT NULL COMMENT '(DC2Type:date_immutable)'
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE
              materiel_informatique
            CHANGE
              date_mise_en_service garantie_jusquau DATE DEFAULT NULL COMMENT '(DC2Type:date_immutable)'
        SQL);
    }
}
