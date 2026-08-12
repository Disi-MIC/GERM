<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260811161705 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove cout from maintenance and licence_logiciel — cost tracking is out of scope for the IT parc';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE licence_logiciel DROP cout');
        $this->addSql('ALTER TABLE maintenance DROP cout');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE licence_logiciel ADD cout NUMERIC(12, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE maintenance ADD cout NUMERIC(12, 2) DEFAULT NULL');
    }
}
