<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261027000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les details structures aux entretiens de parcours E2C';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE entretienparcours ADD details JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE entretienparcours DROP details');
    }
}