<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261123225427 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les champs facultatifs de completion des beneficiaires externes.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE beneficiaire_externe ADD diplome VARCHAR(50) DEFAULT NULL");
        $this->addSql("ALTER TABLE beneficiaire_externe ADD ancien_metier VARCHAR(80) DEFAULT NULL");
        $this->addSql("ALTER TABLE beneficiaire_externe ADD disponibilite VARCHAR(30) DEFAULT NULL");
        $this->addSql("ALTER TABLE beneficiaire_externe ADD disponible_le DATE DEFAULT NULL");
        $this->addSql("ALTER TABLE beneficiaire_externe ADD contact_urgence_nom VARCHAR(120) DEFAULT NULL");
        $this->addSql("ALTER TABLE beneficiaire_externe ADD contact_urgence_tel VARCHAR(30) DEFAULT NULL");
        $this->addSql("ALTER TABLE beneficiaire_externe ADD contact_urgence_lien VARCHAR(120) DEFAULT NULL");
        $this->addSql("ALTER TABLE beneficiaire_externe ADD experience TEXT DEFAULT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE beneficiaire_externe DROP diplome");
        $this->addSql("ALTER TABLE beneficiaire_externe DROP ancien_metier");
        $this->addSql("ALTER TABLE beneficiaire_externe DROP disponibilite");
        $this->addSql("ALTER TABLE beneficiaire_externe DROP disponible_le");
        $this->addSql("ALTER TABLE beneficiaire_externe DROP contact_urgence_nom");
        $this->addSql("ALTER TABLE beneficiaire_externe DROP contact_urgence_tel");
        $this->addSql("ALTER TABLE beneficiaire_externe DROP contact_urgence_lien");
        $this->addSql("ALTER TABLE beneficiaire_externe DROP experience");
    }
}