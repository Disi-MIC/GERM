<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260818132551 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute demande_decision.date_prise_de_service, renseignée par les nouveaux agents.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE demande_decision ADD date_prise_de_service DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE demande_decision DROP date_prise_de_service');
    }
}
