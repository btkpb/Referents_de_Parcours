<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261002122638 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE Admission (id INT AUTO_INCREMENT NOT NULL, decision VARCHAR(30) NOT NULL, metierAffecte VARCHAR(120) DEFAULT NULL, dateAdmission DATETIME DEFAULT NULL, codeAdmission VARCHAR(40) DEFAULT NULL, candidat_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_764A80E98D0EB82 (candidat_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE AffectationChantier (id INT AUTO_INCREMENT NOT NULL, dateDebut DATE NOT NULL, dateFin DATE DEFAULT NULL, statut VARCHAR(30) NOT NULL, candidat_id INT DEFAULT NULL, chantier_id INT DEFAULT NULL, INDEX IDX_ADAFC768D0EB82 (candidat_id), INDEX IDX_ADAFC76D0C0049D (chantier_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE Campagne (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(160) NOT NULL, dateDebut DATE NOT NULL, dateFin DATE NOT NULL, active TINYINT NOT NULL, ageMin INT DEFAULT NULL, ageMax INT DEFAULT NULL, quotaGlobal INT DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE Candidat (id INT AUTO_INCREMENT NOT NULL, numero VARCHAR(30) NOT NULL, nom VARCHAR(100) NOT NULL, prenoms VARCHAR(160) NOT NULL, sexe VARCHAR(10) NOT NULL, dateNaissance DATE NOT NULL, lieuNaissance VARCHAR(160) DEFAULT NULL, lieuResidence VARCHAR(160) DEFAULT NULL, nationalite VARCHAR(100) NOT NULL, telephone VARCHAR(30) NOT NULL, telephoneAlt VARCHAR(30) DEFAULT NULL, pieceIdentite VARCHAR(40) NOT NULL, pieceIdentiteFichier VARCHAR(255) DEFAULT NULL, niveau VARCHAR(40) NOT NULL, diplome VARCHAR(50) NOT NULL, diplomeFichier VARCHAR(255) DEFAULT NULL, metier VARCHAR(80) NOT NULL, ancienMetier VARCHAR(80) NOT NULL, disponibilite VARCHAR(30) NOT NULL, disponibleLe DATE DEFAULT NULL, contactUrgenceNom VARCHAR(120) DEFAULT NULL, contactUrgenceTel VARCHAR(30) DEFAULT NULL, contactUrgenceLien VARCHAR(120) DEFAULT NULL, experience LONGTEXT DEFAULT NULL, statut VARCHAR(30) NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, campagne_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_93C3D627F55AE19E (numero), INDEX IDX_93C3D62716227374 (campagne_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE Chantier (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(160) NOT NULL, localisation VARCHAR(180) NOT NULL, entreprise VARCHAR(160) DEFAULT NULL, statut VARCHAR(40) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE Emploi (id INT AUTO_INCREMENT NOT NULL, dateInsertion DATE DEFAULT NULL, employeur VARCHAR(180) DEFAULT NULL, poste VARCHAR(120) DEFAULT NULL, statut VARCHAR(30) NOT NULL, observations LONGTEXT DEFAULT NULL, candidat_id INT DEFAULT NULL, INDEX IDX_730CB5CC8D0EB82 (candidat_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE Entretien (id INT AUTO_INCREMENT NOT NULL, dateHeure DATETIME NOT NULL, lieu VARCHAR(180) NOT NULL, nombreJury INT NOT NULL, statut VARCHAR(30) NOT NULL, juryNoms VARCHAR(255) DEFAULT NULL, convocationEnvoyeeAt VARCHAR(255) DEFAULT NULL, candidat_id INT DEFAULT NULL, INDEX IDX_A9A954798D0EB82 (candidat_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE EntretienParcours (id INT AUTO_INCREMENT NOT NULL, typeEntretien VARCHAR(40) NOT NULL, dateEntretien DATE NOT NULL, synthese LONGTEXT DEFAULT NULL, planAction LONGTEXT DEFAULT NULL, statut VARCHAR(30) NOT NULL, candidat_id INT DEFAULT NULL, INDEX IDX_4766466B8D0EB82 (candidat_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE Evaluation (id INT AUTO_INCREMENT NOT NULL, scoreMotivation DOUBLE PRECISION NOT NULL, scoreProjet DOUBLE PRECISION NOT NULL, scoreTechnique DOUBLE PRECISION NOT NULL, scoreCommunication DOUBLE PRECISION NOT NULL, scoreSocial DOUBLE PRECISION NOT NULL, scoreMobilite DOUBLE PRECISION NOT NULL, scoreFinances DOUBLE PRECISION NOT NULL, scoreLogement DOUBLE PRECISION NOT NULL, scoreSante DOUBLE PRECISION NOT NULL, decision VARCHAR(20) NOT NULL, observations LONGTEXT DEFAULT NULL, entretien_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_5C7EA6A5548DCEA2 (entretien_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE VisiteMedicale (id INT AUTO_INCREMENT NOT NULL, dateHeure DATETIME NOT NULL, centre VARCHAR(180) NOT NULL, aptitude VARCHAR(20) NOT NULL, certificat VARCHAR(255) DEFAULT NULL, photo VARCHAR(255) DEFAULT NULL, observations LONGTEXT DEFAULT NULL, candidat_id INT DEFAULT NULL, INDEX IDX_EE4897628D0EB82 (candidat_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE app_user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, nom VARCHAR(120) NOT NULL, prenoms VARCHAR(120) NOT NULL, profil VARCHAR(40) NOT NULL, active TINYINT NOT NULL, UNIQUE INDEX UNIQ_88BDF3E9E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE Admission ADD CONSTRAINT FK_764A80E98D0EB82 FOREIGN KEY (candidat_id) REFERENCES Candidat (id)');
        $this->addSql('ALTER TABLE AffectationChantier ADD CONSTRAINT FK_ADAFC768D0EB82 FOREIGN KEY (candidat_id) REFERENCES Candidat (id)');
        $this->addSql('ALTER TABLE AffectationChantier ADD CONSTRAINT FK_ADAFC76D0C0049D FOREIGN KEY (chantier_id) REFERENCES Chantier (id)');
        $this->addSql('ALTER TABLE Candidat ADD CONSTRAINT FK_93C3D62716227374 FOREIGN KEY (campagne_id) REFERENCES Campagne (id)');
        $this->addSql('ALTER TABLE Emploi ADD CONSTRAINT FK_730CB5CC8D0EB82 FOREIGN KEY (candidat_id) REFERENCES Candidat (id)');
        $this->addSql('ALTER TABLE Entretien ADD CONSTRAINT FK_A9A954798D0EB82 FOREIGN KEY (candidat_id) REFERENCES Candidat (id)');
        $this->addSql('ALTER TABLE EntretienParcours ADD CONSTRAINT FK_4766466B8D0EB82 FOREIGN KEY (candidat_id) REFERENCES Candidat (id)');
        $this->addSql('ALTER TABLE Evaluation ADD CONSTRAINT FK_5C7EA6A5548DCEA2 FOREIGN KEY (entretien_id) REFERENCES Entretien (id)');
        $this->addSql('ALTER TABLE VisiteMedicale ADD CONSTRAINT FK_EE4897628D0EB82 FOREIGN KEY (candidat_id) REFERENCES Candidat (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE Admission DROP FOREIGN KEY FK_764A80E98D0EB82');
        $this->addSql('ALTER TABLE AffectationChantier DROP FOREIGN KEY FK_ADAFC768D0EB82');
        $this->addSql('ALTER TABLE AffectationChantier DROP FOREIGN KEY FK_ADAFC76D0C0049D');
        $this->addSql('ALTER TABLE Candidat DROP FOREIGN KEY FK_93C3D62716227374');
        $this->addSql('ALTER TABLE Emploi DROP FOREIGN KEY FK_730CB5CC8D0EB82');
        $this->addSql('ALTER TABLE Entretien DROP FOREIGN KEY FK_A9A954798D0EB82');
        $this->addSql('ALTER TABLE EntretienParcours DROP FOREIGN KEY FK_4766466B8D0EB82');
        $this->addSql('ALTER TABLE Evaluation DROP FOREIGN KEY FK_5C7EA6A5548DCEA2');
        $this->addSql('ALTER TABLE VisiteMedicale DROP FOREIGN KEY FK_EE4897628D0EB82');
        $this->addSql('DROP TABLE Admission');
        $this->addSql('DROP TABLE AffectationChantier');
        $this->addSql('DROP TABLE Campagne');
        $this->addSql('DROP TABLE Candidat');
        $this->addSql('DROP TABLE Chantier');
        $this->addSql('DROP TABLE Emploi');
        $this->addSql('DROP TABLE Entretien');
        $this->addSql('DROP TABLE EntretienParcours');
        $this->addSql('DROP TABLE Evaluation');
        $this->addSql('DROP TABLE VisiteMedicale');
        $this->addSql('DROP TABLE app_user');
    }
}

