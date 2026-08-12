<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260811155528 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed liste_valeur for the 3 new logiciel categories (OS, antivirus, bureautique)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            INSERT INTO liste_valeur (categorie, code, libelle, actif) VALUES
                ('logiciel-os', 'windows_7', 'Windows 7', 1),
                ('logiciel-os', 'windows_10', 'Windows 10', 1),
                ('logiciel-os', 'windows_11', 'Windows 11', 1),
                ('logiciel-antivirus', 'kaspersky_plus', 'Kaspersky Plus', 1),
                ('logiciel-antivirus', 'kaspersky_server', 'Kaspersky Endpoint Security for Business (Server)', 1),
                ('logiciel-bureautique', 'office_2016', 'Microsoft Office 2016', 1),
                ('logiciel-bureautique', 'office_2019', 'Microsoft Office 2019', 1),
                ('logiciel-bureautique', 'office_2021', 'Microsoft Office 2021', 1),
                ('logiciel-bureautique', 'office_365', 'Microsoft Office 365', 1)
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM liste_valeur WHERE categorie IN ('logiciel-os', 'logiciel-antivirus', 'logiciel-bureautique')");
    }
}
