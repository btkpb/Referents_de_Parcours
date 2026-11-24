<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261123150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute le nom de stockage prive du fichier original des lots externes.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            "ALTER TABLE import_beneficiaire_lot
             ADD fichier_stocke VARCHAR(255) DEFAULT NULL"
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            "ALTER TABLE import_beneficiaire_lot
             DROP fichier_stocke"
        );
    }
}