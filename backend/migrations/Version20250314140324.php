<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
<<<<<<<< HEAD:backend/migrations/Version20250317123853.php
final class Version20250317123853 extends AbstractMigration
========
final class Version20250314140324 extends AbstractMigration
>>>>>>>> 4e02060 (design planning avec filtre fonctionnels):backend/migrations/Version20250314140324.php
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE coach ADD description LONGTEXT DEFAULT NULL, ADD image_filename VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE coach DROP description, DROP image_filename');
    }
}
