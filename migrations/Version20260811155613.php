<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260811155613 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add systeme_exploitation/suite_bureautique/antivirus (nullable ListeValeur FKs) to materiel_informatique';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE materiel_informatique ADD systeme_exploitation_id INT DEFAULT NULL, ADD suite_bureautique_id INT DEFAULT NULL, ADD antivirus_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE materiel_informatique ADD CONSTRAINT FK_A0C8655B1B83D934 FOREIGN KEY (systeme_exploitation_id) REFERENCES liste_valeur (id)');
        $this->addSql('ALTER TABLE materiel_informatique ADD CONSTRAINT FK_A0C8655B4D8C9B8E FOREIGN KEY (suite_bureautique_id) REFERENCES liste_valeur (id)');
        $this->addSql('ALTER TABLE materiel_informatique ADD CONSTRAINT FK_A0C8655B26A1082F FOREIGN KEY (antivirus_id) REFERENCES liste_valeur (id)');
        $this->addSql('CREATE INDEX IDX_A0C8655B1B83D934 ON materiel_informatique (systeme_exploitation_id)');
        $this->addSql('CREATE INDEX IDX_A0C8655B4D8C9B8E ON materiel_informatique (suite_bureautique_id)');
        $this->addSql('CREATE INDEX IDX_A0C8655B26A1082F ON materiel_informatique (antivirus_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE materiel_informatique DROP FOREIGN KEY FK_A0C8655B1B83D934');
        $this->addSql('ALTER TABLE materiel_informatique DROP FOREIGN KEY FK_A0C8655B4D8C9B8E');
        $this->addSql('ALTER TABLE materiel_informatique DROP FOREIGN KEY FK_A0C8655B26A1082F');
        $this->addSql('DROP INDEX IDX_A0C8655B1B83D934 ON materiel_informatique');
        $this->addSql('DROP INDEX IDX_A0C8655B4D8C9B8E ON materiel_informatique');
        $this->addSql('DROP INDEX IDX_A0C8655B26A1082F ON materiel_informatique');
        $this->addSql('ALTER TABLE materiel_informatique DROP systeme_exploitation_id, DROP suite_bureautique_id, DROP antivirus_id');
    }
}
