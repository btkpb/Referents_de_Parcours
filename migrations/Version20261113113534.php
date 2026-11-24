<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261113113534 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE affectation_entreprise_apprenti (id INT AUTO_INCREMENT NOT NULL, dateDebut DATE NOT NULL, dateFin DATE DEFAULT NULL, statut VARCHAR(30) NOT NULL, observations LONGTEXT DEFAULT NULL, createdAt DATETIME NOT NULL, candidat_id INT NOT NULL, entreprise_id INT NOT NULL, chantier_id INT DEFAULT NULL, creePar_id INT DEFAULT NULL, INDEX IDX_CBCD819E8D0EB82 (candidat_id), INDEX IDX_CBCD819EA4AEAFEA (entreprise_id), INDEX IDX_CBCD819ED0C0049D (chantier_id), INDEX IDX_CBCD819EB660D3F4 (creePar_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE entreprise_stage (id INT AUTO_INCREMENT NOT NULL, raisonSociale VARCHAR(180) NOT NULL, sigle VARCHAR(80) DEFAULT NULL, secteurActivite VARCHAR(160) DEFAULT NULL, adresse VARCHAR(255) DEFAULT NULL, ville VARCHAR(120) DEFAULT NULL, telephone VARCHAR(30) DEFAULT NULL, telephoneSecondaire VARCHAR(30) DEFAULT NULL, email VARCHAR(180) DEFAULT NULL, nomResponsable VARCHAR(160) DEFAULT NULL, fonctionResponsable VARCHAR(120) DEFAULT NULL, telephoneResponsable VARCHAR(30) DEFAULT NULL, nomTuteur VARCHAR(160) DEFAULT NULL, fonctionTuteur VARCHAR(120) DEFAULT NULL, telephoneTuteur VARCHAR(30) DEFAULT NULL, emailTuteur VARCHAR(180) DEFAULT NULL, logoFichier VARCHAR(255) DEFAULT NULL, cachetFichier VARCHAR(255) DEFAULT NULL, statut VARCHAR(30) NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE affectation_entreprise_apprenti ADD CONSTRAINT FK_CBCD819E8D0EB82 FOREIGN KEY (candidat_id) REFERENCES candidat (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE affectation_entreprise_apprenti ADD CONSTRAINT FK_CBCD819EA4AEAFEA FOREIGN KEY (entreprise_id) REFERENCES entreprise_stage (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE affectation_entreprise_apprenti ADD CONSTRAINT FK_CBCD819ED0C0049D FOREIGN KEY (chantier_id) REFERENCES chantier (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE affectation_entreprise_apprenti ADD CONSTRAINT FK_CBCD819EB660D3F4 FOREIGN KEY (creePar_id) REFERENCES app_user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE affectation_entreprise_apprenti DROP FOREIGN KEY FK_CBCD819E8D0EB82');
        $this->addSql('ALTER TABLE affectation_entreprise_apprenti DROP FOREIGN KEY FK_CBCD819EA4AEAFEA');
        $this->addSql('ALTER TABLE affectation_entreprise_apprenti DROP FOREIGN KEY FK_CBCD819ED0C0049D');
        $this->addSql('ALTER TABLE affectation_entreprise_apprenti DROP FOREIGN KEY FK_CBCD819EB660D3F4');
        $this->addSql('DROP TABLE affectation_entreprise_apprenti');
        $this->addSql('DROP TABLE entreprise_stage');
    }
}
