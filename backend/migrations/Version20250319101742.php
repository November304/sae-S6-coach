<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250319101742 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE demande_annulation (id INT AUTO_INCREMENT NOT NULL, seance_id INT DEFAULT NULL, responsable_id INT DEFAULT NULL, motif VARCHAR(255) NOT NULL, date_demande DATETIME NOT NULL, statut VARCHAR(20) NOT NULL, date_traitement DATETIME DEFAULT NULL, INDEX IDX_F4A37725E3797A94 (seance_id), INDEX IDX_F4A3772553C59D72 (responsable_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE presence (id INT AUTO_INCREMENT NOT NULL, seance_id INT NOT NULL, sportif_id INT NOT NULL, present VARCHAR(10) NOT NULL, INDEX IDX_6977C7A5E3797A94 (seance_id), INDEX IDX_6977C7A5FFB7083B (sportif_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE demande_annulation ADD CONSTRAINT FK_F4A37725E3797A94 FOREIGN KEY (seance_id) REFERENCES seance (id)');
        $this->addSql('ALTER TABLE demande_annulation ADD CONSTRAINT FK_F4A3772553C59D72 FOREIGN KEY (responsable_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE presence ADD CONSTRAINT FK_6977C7A5E3797A94 FOREIGN KEY (seance_id) REFERENCES seance (id)');
        $this->addSql('ALTER TABLE presence ADD CONSTRAINT FK_6977C7A5FFB7083B FOREIGN KEY (sportif_id) REFERENCES sportif (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE demande_annulation DROP FOREIGN KEY FK_F4A37725E3797A94');
        $this->addSql('ALTER TABLE demande_annulation DROP FOREIGN KEY FK_F4A3772553C59D72');
        $this->addSql('ALTER TABLE presence DROP FOREIGN KEY FK_6977C7A5E3797A94');
        $this->addSql('ALTER TABLE presence DROP FOREIGN KEY FK_6977C7A5FFB7083B');
        $this->addSql('DROP TABLE demande_annulation');
        $this->addSql('DROP TABLE presence');
    }
}
