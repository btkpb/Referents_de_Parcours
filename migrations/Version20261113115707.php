<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261113115707 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE evaluation_competence_entreprise (id INT AUTO_INCREMENT NOT NULL, referenceRf VARCHAR(100) DEFAULT NULL, intituleCompetence LONGTEXT NOT NULL, niveau VARCHAR(40) DEFAULT NULL, observations LONGTEXT DEFAULT NULL, ordre INT NOT NULL, evaluation_id INT NOT NULL, INDEX IDX_F821D483456C5646 (evaluation_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE evaluation_comportement_entreprise (id INT AUTO_INCREMENT NOT NULL, critere VARCHAR(50) NOT NULL, appreciation VARCHAR(30) DEFAULT NULL, observation LONGTEXT DEFAULT NULL, evaluation_id INT NOT NULL, INDEX IDX_7E106D65456C5646 (evaluation_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_EVAL_COMPORTEMENT_CRITERE ON evaluation_comportement_entreprise (evaluation_id, critere)');
        $this->addSql('CREATE TABLE evaluation_entreprise (id INT AUTO_INCREMENT NOT NULL, nomApprenti VARCHAR(180) DEFAULT NULL, prenomsApprenti VARCHAR(180) DEFAULT NULL, telephoneApprenti VARCHAR(40) DEFAULT NULL, metier VARCHAR(180) DEFAULT NULL, intituleProjet VARCHAR(255) DEFAULT NULL, nomReferent VARCHAR(180) DEFAULT NULL, nomCentre VARCHAR(180) DEFAULT NULL, nomFormateur VARCHAR(180) DEFAULT NULL, telephoneFormateur VARCHAR(40) DEFAULT NULL, nomEntreprise VARCHAR(255) DEFAULT NULL, nomResponsable VARCHAR(180) DEFAULT NULL, nomTuteur VARCHAR(180) DEFAULT NULL, fonctionTuteur VARCHAR(180) DEFAULT NULL, telephoneTuteur VARCHAR(40) DEFAULT NULL, dateSuivi DATE NOT NULL, periodeSuiviDebut DATE DEFAULT NULL, periodeSuiviFin DATE DEFAULT NULL, pointsForts LONGTEXT DEFAULT NULL, pointsAAmeliorer LONGTEXT DEFAULT NULL, bilanGeneral VARCHAR(40) DEFAULT NULL, avisMaitreStage LONGTEXT DEFAULT NULL, lieuSignature VARCHAR(180) DEFAULT NULL, statut VARCHAR(30) NOT NULL, signatureMaitreNom VARCHAR(180) DEFAULT NULL, signatureMaitreDate DATETIME DEFAULT NULL, signatureReferentNom VARCHAR(180) DEFAULT NULL, signatureReferentDate DATETIME DEFAULT NULL, pdfFichier VARCHAR(255) DEFAULT NULL, scanSigneFichier VARCHAR(255) DEFAULT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, candidat_id INT NOT NULL, affectation_entreprise_id INT NOT NULL, maitre_stage_id INT NOT NULL, referent_id INT DEFAULT NULL, INDEX IDX_E71EBD928D0EB82 (candidat_id), INDEX IDX_E71EBD92FB8BCD57 (affectation_entreprise_id), INDEX IDX_E71EBD92ADCA9431 (maitre_stage_id), INDEX IDX_E71EBD9235E47E35 (referent_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE evaluation_competence_entreprise ADD CONSTRAINT FK_F821D483456C5646 FOREIGN KEY (evaluation_id) REFERENCES evaluation_entreprise (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE evaluation_comportement_entreprise ADD CONSTRAINT FK_7E106D65456C5646 FOREIGN KEY (evaluation_id) REFERENCES evaluation_entreprise (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE evaluation_entreprise ADD CONSTRAINT FK_E71EBD928D0EB82 FOREIGN KEY (candidat_id) REFERENCES candidat (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE evaluation_entreprise ADD CONSTRAINT FK_E71EBD92FB8BCD57 FOREIGN KEY (affectation_entreprise_id) REFERENCES affectation_entreprise_apprenti (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE evaluation_entreprise ADD CONSTRAINT FK_E71EBD92ADCA9431 FOREIGN KEY (maitre_stage_id) REFERENCES app_user (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE evaluation_entreprise ADD CONSTRAINT FK_E71EBD9235E47E35 FOREIGN KEY (referent_id) REFERENCES app_user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE evaluation_competence_entreprise DROP FOREIGN KEY FK_F821D483456C5646');
        $this->addSql('ALTER TABLE evaluation_comportement_entreprise DROP FOREIGN KEY FK_7E106D65456C5646');
        $this->addSql('ALTER TABLE evaluation_entreprise DROP FOREIGN KEY FK_E71EBD928D0EB82');
        $this->addSql('ALTER TABLE evaluation_entreprise DROP FOREIGN KEY FK_E71EBD92FB8BCD57');
        $this->addSql('ALTER TABLE evaluation_entreprise DROP FOREIGN KEY FK_E71EBD92ADCA9431');
        $this->addSql('ALTER TABLE evaluation_entreprise DROP FOREIGN KEY FK_E71EBD9235E47E35');
        $this->addSql('DROP TABLE evaluation_competence_entreprise');
        $this->addSql('DROP TABLE evaluation_comportement_entreprise');
        $this->addSql('DROP TABLE evaluation_entreprise');
    }
}
