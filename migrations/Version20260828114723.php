<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260828114723 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Ajoute materiel_informatique.numero_telephone — numéro de poste interne (4 chiffres) pour un matériel de type Téléphone.";
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE materiel_informatique ADD numero_telephone VARCHAR(4) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE materiel_informatique DROP numero_telephone');
    }
}
