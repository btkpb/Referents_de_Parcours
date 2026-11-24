<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261112120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Séparation des observations de planification et des observations médicales.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE visitemedicale
             ADD observationsPlanification LONGTEXT DEFAULT NULL'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE visitemedicale
             DROP observationsPlanification'
        );
    }
}
