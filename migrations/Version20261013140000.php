<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261013140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création du catalogue public des métiers de formation.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE metier_catalogue (
                id INT AUTO_INCREMENT NOT NULL,
                nom VARCHAR(120) NOT NULL,
                secteur VARCHAR(120) NOT NULL,
                description LONGTEXT DEFAULT NULL,
                actif TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL,
                UNIQUE INDEX UNIQ_METIER_CATALOGUE_NOM (nom),
                INDEX IDX_METIER_CATALOGUE_SECTEUR (secteur),
                INDEX IDX_METIER_CATALOGUE_ACTIF (actif),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4
              COLLATE `utf8mb4_unicode_ci`
              ENGINE = InnoDB
        ");

        $this->addSql("
            INSERT INTO metier_catalogue
                (nom, secteur, description, actif, created_at)
            VALUES
                ('Maçon', 'Bâtiment et travaux publics',
                 'Réalisation des ouvrages de maçonnerie et travaux de construction.', 1, NOW()),
                ('Menuisier-Coffreur', 'Bâtiment et travaux publics',
                 'Fabrication et mise en place des coffrages et ouvrages de menuiserie de chantier.', 1, NOW()),
                ('Peintre', 'Bâtiment et travaux publics',
                 'Préparation des surfaces et réalisation des travaux de peinture.', 1, NOW()),
                ('Carreleur', 'Bâtiment et travaux publics',
                 'Préparation des supports et pose des revêtements carrelés.', 1, NOW()),
                ('Staffeur-Plaquiste', 'Bâtiment et travaux publics',
                 'Réalisation des ouvrages en staff, cloisons et plafonds.', 1, NOW()),
                ('Ferrailleur', 'Bâtiment et travaux publics',
                 'Préparation et mise en place des armatures métalliques du béton armé.', 1, NOW()),
                ('Ferronnier', 'Bâtiment et travaux publics',
                 'Fabrication et assemblage d’ouvrages métalliques.', 1, NOW())
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE metier_catalogue');
    }
}