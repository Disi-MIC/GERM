<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260812100408 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add ticket escalation levels (NiveauTicket) and ticket_escalade history journal';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ticket_escalade (id INT AUTO_INCREMENT NOT NULL, ticket_id INT NOT NULL, par_id INT DEFAULT NULL, de_niveau VARCHAR(20) NOT NULL, vers_niveau VARCHAR(20) NOT NULL, commentaire LONGTEXT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_8B5EB650700047D2 (ticket_id), INDEX IDX_8B5EB650468486AA (par_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ticket_escalade ADD CONSTRAINT FK_8B5EB650700047D2 FOREIGN KEY (ticket_id) REFERENCES ticket_incident (id)');
        $this->addSql('ALTER TABLE ticket_escalade ADD CONSTRAINT FK_8B5EB650468486AA FOREIGN KEY (par_id) REFERENCES personnel (id)');
        $this->addSql('ALTER TABLE ticket_incident ADD niveau VARCHAR(20) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ticket_escalade DROP FOREIGN KEY FK_8B5EB650700047D2');
        $this->addSql('ALTER TABLE ticket_escalade DROP FOREIGN KEY FK_8B5EB650468486AA');
        $this->addSql('DROP TABLE ticket_escalade');
        $this->addSql('ALTER TABLE ticket_incident DROP niveau');
    }
}
