<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260813153935 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'user.nom/prenom ne sont plus qu\'un repli pour les comptes sans fiche agent liée (User::getNom() délègue à Personnel dès que le lien existe) — supprime la copie devenue redondante pour les comptes déjà liés.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE
              user
            CHANGE
              nom nom VARCHAR(100) DEFAULT NULL,
            CHANGE
              prenom prenom VARCHAR(100) DEFAULT NULL
        SQL);

        // Un compte lié n'a plus sa propre copie de nom/prenom : User::getNom()
        // délègue désormais à Personnel, donc la valeur stockée ici ne serait
        // plus jamais lue et ne ferait que risquer de diverger si on la laissait.
        $this->addSql(<<<'SQL'
            UPDATE user u
            INNER JOIN personnel p ON p.user_id = u.id
            SET u.nom = NULL, u.prenom = NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // Restaure une copie sur User avant de remettre NOT NULL, sans quoi
        // les comptes liés (mis à NULL par le up()) feraient échouer l'ALTER.
        $this->addSql(<<<'SQL'
            UPDATE user u
            INNER JOIN personnel p ON p.user_id = u.id
            SET u.nom = p.nom, u.prenom = p.prenom
            WHERE u.nom IS NULL OR u.prenom IS NULL
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE
              `user`
            CHANGE
              nom nom VARCHAR(100) NOT NULL,
            CHANGE
              prenom prenom VARCHAR(100) NOT NULL
        SQL);
    }
}
