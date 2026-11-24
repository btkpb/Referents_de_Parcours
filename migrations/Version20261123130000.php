<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261123130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout du circuit d integration externe des beneficiaires et distinction des admissions internes et externes.';
    }

    public function up(Schema $schema): void
    {
        // Lot d'importation des beneficiaires externes.
        $this->addSql("
            CREATE TABLE import_beneficiaire_lot (
                id INT AUTO_INCREMENT NOT NULL,
                programme VARCHAR(180) NOT NULL,
                partenaire VARCHAR(180) NOT NULL,
                reference_lot VARCHAR(100) DEFAULT NULL,
                date_import DATETIME NOT NULL,
                fichier_original VARCHAR(255) NOT NULL,
                importe_par_id INT NOT NULL,
                created_at DATETIME NOT NULL,
                INDEX IDX_IMPORT_LOT_DATE (date_import),
                INDEX IDX_IMPORT_LOT_IMPORTATEUR (importe_par_id),
                PRIMARY KEY(id),
                CONSTRAINT FK_IMPORT_LOT_IMPORTATEUR
                    FOREIGN KEY (importe_par_id)
                    REFERENCES app_user (id)
                    ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");

        // Beneficiaires importes : aucun dossier candidat n'est cree a ce stade.
        $this->addSql("
            CREATE TABLE beneficiaire_externe (
                id INT AUTO_INCREMENT NOT NULL,
                nom VARCHAR(100) NOT NULL,
                prenoms VARCHAR(160) NOT NULL,
                sexe VARCHAR(10) NOT NULL,
                date_naissance DATE NOT NULL,
                telephone VARCHAR(30) NOT NULL,
                telephone_secondaire VARCHAR(30) DEFAULT NULL,
                email VARCHAR(180) DEFAULT NULL,
                lieu_naissance VARCHAR(160) DEFAULT NULL,
                lieu_residence VARCHAR(160) DEFAULT NULL,
                nationalite VARCHAR(100) DEFAULT NULL,
                type_piece VARCHAR(40) DEFAULT NULL,
                numero_piece_identite VARCHAR(100) DEFAULT NULL,
                metier VARCHAR(80) NOT NULL,
                niveau_etude VARCHAR(80) DEFAULT NULL,
                reference_beneficiaire VARCHAR(120) DEFAULT NULL,
                numero_ligne INT DEFAULT NULL,
                statut_validation VARCHAR(30) NOT NULL,
                observations_validation TEXT DEFAULT NULL,
                lot_import_id INT NOT NULL,
                valide_par_id INT DEFAULT NULL,
                date_validation DATETIME DEFAULT NULL,
                candidat_id INT DEFAULT NULL,
                created_at DATETIME NOT NULL,
                INDEX IDX_BENEF_EXT_LOT (lot_import_id),
                INDEX IDX_BENEF_EXT_STATUT (statut_validation),
                INDEX IDX_BENEF_EXT_PIECE (type_piece, numero_piece_identite),
                INDEX IDX_BENEF_EXT_VALIDATEUR (valide_par_id),
                UNIQUE INDEX UNIQ_BENEF_EXT_CANDIDAT (candidat_id),
                PRIMARY KEY(id),
                CONSTRAINT FK_BENEF_EXT_LOT
                    FOREIGN KEY (lot_import_id)
                    REFERENCES import_beneficiaire_lot (id)
                    ON DELETE RESTRICT,
                CONSTRAINT FK_BENEF_EXT_VALIDATEUR
                    FOREIGN KEY (valide_par_id)
                    REFERENCES app_user (id)
                    ON DELETE SET NULL,
                CONSTRAINT FK_BENEF_EXT_CANDIDAT
                    FOREIGN KEY (candidat_id)
                    REFERENCES candidat (id)
                    ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");

        // Numero de piece facultatif dans le dossier candidat.
        $this->addSql("
            ALTER TABLE candidat
            ADD numero_piece_identite VARCHAR(100) DEFAULT NULL
        ");

        // Les dossiers existants restent des admissions internes.
        $this->addSql("
            ALTER TABLE admission
            ADD type_admission VARCHAR(40) NOT NULL DEFAULT 'RECRUTEMENT_INTERNE',
            ADD origine_dossier VARCHAR(20) NOT NULL DEFAULT 'INTERNE',
            ADD lot_integration_externe_id INT DEFAULT NULL
        ");

        $this->addSql("
            ALTER TABLE admission
            ADD INDEX IDX_ADMISSION_LOT_INTEGRATION (lot_integration_externe_id)
        ");

        $this->addSql("
            ALTER TABLE admission
            ADD CONSTRAINT FK_ADMISSION_LOT_INTEGRATION
            FOREIGN KEY (lot_integration_externe_id)
            REFERENCES import_beneficiaire_lot (id)
            ON DELETE RESTRICT
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE admission
            DROP FOREIGN KEY FK_ADMISSION_LOT_INTEGRATION
        ");

        $this->addSql("
            ALTER TABLE admission
            DROP INDEX IDX_ADMISSION_LOT_INTEGRATION
        ");

        $this->addSql("
            ALTER TABLE admission
            DROP COLUMN lot_integration_externe_id,
            DROP COLUMN origine_dossier,
            DROP COLUMN type_admission
        ");

        $this->addSql("
            ALTER TABLE candidat
            DROP COLUMN numero_piece_identite
        ");

        $this->addSql("DROP TABLE beneficiaire_externe");
        $this->addSql("DROP TABLE import_beneficiaire_lot");
    }
}