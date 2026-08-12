<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260812090228 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove nombre_postes from licence_logiciel — now counted live from materiel usage instead of stored';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE licence_logiciel DROP nombre_postes');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE licence_logiciel ADD nombre_postes INT DEFAULT NULL');
    }
}
