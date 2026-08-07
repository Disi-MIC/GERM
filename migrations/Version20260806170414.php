<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260806170414 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE carte_professionnelle ADD validee_par_id INT DEFAULT NULL, ADD validee_par_admin_rh TINYINT(1) NOT NULL DEFAULT 0, ADD validee_le DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE carte_professionnelle ADD CONSTRAINT FK_157320EA629A7BB2 FOREIGN KEY (validee_par_id) REFERENCES `user` (id)');
        $this->addSql('CREATE INDEX IDX_157320EA629A7BB2 ON carte_professionnelle (validee_par_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE carte_professionnelle DROP FOREIGN KEY FK_157320EA629A7BB2');
        $this->addSql('DROP INDEX IDX_157320EA629A7BB2 ON carte_professionnelle');
        $this->addSql('ALTER TABLE carte_professionnelle DROP validee_par_id, DROP validee_par_admin_rh, DROP validee_le');
    }
}
