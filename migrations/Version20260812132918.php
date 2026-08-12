<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260812132918 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'systemeExploitation/suiteBureautique/antivirus pointent désormais vers une LicenceLogiciel précise (et non plus le seul produit ListeValeur), pour un décompte "postes couverts" par ligne de licence.';
    }

    public function up(Schema $schema): void
    {
        // Les valeurs existantes référencent des liste_valeur.id, incompatibles
        // avec la nouvelle contrainte vers licence_logiciel : remises à NULL,
        // à ressaisir depuis le registre des licences (voir description).
        $this->addSql('UPDATE materiel_informatique SET systeme_exploitation_id = NULL, suite_bureautique_id = NULL, antivirus_id = NULL');
        $this->addSql('ALTER TABLE materiel_informatique DROP FOREIGN KEY FK_A0C8655B1B83D934');
        $this->addSql('ALTER TABLE materiel_informatique DROP FOREIGN KEY FK_A0C8655B26A1082F');
        $this->addSql('ALTER TABLE materiel_informatique DROP FOREIGN KEY FK_A0C8655B4D8C9B8E');
        $this->addSql('ALTER TABLE materiel_informatique ADD CONSTRAINT FK_A0C8655B1B83D934 FOREIGN KEY (systeme_exploitation_id) REFERENCES licence_logiciel (id)');
        $this->addSql('ALTER TABLE materiel_informatique ADD CONSTRAINT FK_A0C8655B26A1082F FOREIGN KEY (antivirus_id) REFERENCES licence_logiciel (id)');
        $this->addSql('ALTER TABLE materiel_informatique ADD CONSTRAINT FK_A0C8655B4D8C9B8E FOREIGN KEY (suite_bureautique_id) REFERENCES licence_logiciel (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE materiel_informatique DROP FOREIGN KEY FK_A0C8655B1B83D934');
        $this->addSql('ALTER TABLE materiel_informatique DROP FOREIGN KEY FK_A0C8655B4D8C9B8E');
        $this->addSql('ALTER TABLE materiel_informatique DROP FOREIGN KEY FK_A0C8655B26A1082F');
        $this->addSql('ALTER TABLE materiel_informatique ADD CONSTRAINT FK_A0C8655B1B83D934 FOREIGN KEY (systeme_exploitation_id) REFERENCES liste_valeur (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE materiel_informatique ADD CONSTRAINT FK_A0C8655B4D8C9B8E FOREIGN KEY (suite_bureautique_id) REFERENCES liste_valeur (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE materiel_informatique ADD CONSTRAINT FK_A0C8655B26A1082F FOREIGN KEY (antivirus_id) REFERENCES liste_valeur (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
