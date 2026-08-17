<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260817182823 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute demande_decision.nouvellement_affecte (détermine les pièces attendues : prise de service seule, ou ancienne décision en plus).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE demande_decision ADD nouvellement_affecte TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE demande_decision DROP nouvellement_affecte');
    }
}
