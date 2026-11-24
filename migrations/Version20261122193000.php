<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261122193000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute le paramétrage du nom affiché de l’expéditeur des convocations';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE configuration_email (
                id INT AUTO_INCREMENT NOT NULL,
                cle VARCHAR(100) NOT NULL,
                valeur VARCHAR(255) NOT NULL,
                UNIQUE INDEX UNIQ_CONFIGURATION_EMAIL_CLE (cle),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB'
        );

        $this->addSql(
            'INSERT INTO configuration_email (cle, valeur)
             VALUES (:cle, :valeur)',
            [
                'cle' => 'nom_expediteur_convocation',
                'valeur' => 'Admin Référent de Parcours Plateforme',
            ]
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE configuration_email');
    }
}