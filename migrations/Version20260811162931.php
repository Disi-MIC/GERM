<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260811162931 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove valeur_acquisition from materiel_informatique — financial tracking out of scope for the IT parc';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE materiel_informatique DROP valeur_acquisition');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE materiel_informatique ADD valeur_acquisition NUMERIC(12, 2) DEFAULT NULL');
    }
}
