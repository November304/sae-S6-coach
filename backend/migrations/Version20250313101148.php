<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250313101148 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE fiche_de_paie DROP FOREIGN KEY FK_B3236E136BC6FD7D');
        $this->addSql('DROP INDEX IDX_B3236E136BC6FD7D ON fiche_de_paie');
        $this->addSql('ALTER TABLE fiche_de_paie CHANGE coach_id_id coach_id INT NOT NULL');
        $this->addSql('ALTER TABLE fiche_de_paie ADD CONSTRAINT FK_B3236E133C105691 FOREIGN KEY (coach_id) REFERENCES coach (id)');
        $this->addSql('CREATE INDEX IDX_B3236E133C105691 ON fiche_de_paie (coach_id)');
        $this->addSql('ALTER TABLE seance DROP FOREIGN KEY FK_DF7DFD0E6BC6FD7D');
        $this->addSql('DROP INDEX IDX_DF7DFD0E6BC6FD7D ON seance');
        $this->addSql('ALTER TABLE seance CHANGE coach_id_id coach_id INT NOT NULL');
        $this->addSql('ALTER TABLE seance ADD CONSTRAINT FK_DF7DFD0E3C105691 FOREIGN KEY (coach_id) REFERENCES coach (id)');
        $this->addSql('CREATE INDEX IDX_DF7DFD0E3C105691 ON seance (coach_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE seance DROP FOREIGN KEY FK_DF7DFD0E3C105691');
        $this->addSql('DROP INDEX IDX_DF7DFD0E3C105691 ON seance');
        $this->addSql('ALTER TABLE seance CHANGE coach_id coach_id_id INT NOT NULL');
        $this->addSql('ALTER TABLE seance ADD CONSTRAINT FK_DF7DFD0E6BC6FD7D FOREIGN KEY (coach_id_id) REFERENCES coach (id)');
        $this->addSql('CREATE INDEX IDX_DF7DFD0E6BC6FD7D ON seance (coach_id_id)');
        $this->addSql('ALTER TABLE fiche_de_paie DROP FOREIGN KEY FK_B3236E133C105691');
        $this->addSql('DROP INDEX IDX_B3236E133C105691 ON fiche_de_paie');
        $this->addSql('ALTER TABLE fiche_de_paie CHANGE coach_id coach_id_id INT NOT NULL');
        $this->addSql('ALTER TABLE fiche_de_paie ADD CONSTRAINT FK_B3236E136BC6FD7D FOREIGN KEY (coach_id_id) REFERENCES coach (id)');
        $this->addSql('CREATE INDEX IDX_B3236E136BC6FD7D ON fiche_de_paie (coach_id_id)');
    }
}
