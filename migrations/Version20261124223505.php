<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261124223505 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création du module de contrats de stage : dossiers, versions, signatures, notifications et historique.';
    }

    public function up(Schema $schema): void
    {
        // 1. Dossier permanent : un seul dossier par affectation.
        $this->addSql(<<<'SQL'
            CREATE TABLE contrat_stage (
                id INT AUTO_INCREMENT NOT NULL,
                numero_contrat VARCHAR(50) NOT NULL,
                affectation_id INT NOT NULL,
                version_courante_id INT DEFAULT NULL,
                statut VARCHAR(30) NOT NULL DEFAULT 'BROUILLON',
                cree_par_id INT DEFAULT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY(id),
                UNIQUE INDEX UNIQ_CONTRAT_STAGE_NUMERO (numero_contrat),
                UNIQUE INDEX UNIQ_CONTRAT_STAGE_AFFECTATION (affectation_id),
                INDEX IDX_CONTRAT_STAGE_CREATEUR (cree_par_id),
                INDEX IDX_CONTRAT_STAGE_STATUT (statut),
                CONSTRAINT FK_CONTRAT_STAGE_AFFECTATION
                    FOREIGN KEY (affectation_id)
                    REFERENCES affectation_entreprise_apprenti (id)
                    ON DELETE RESTRICT,
                CONSTRAINT FK_CONTRAT_STAGE_CREATEUR
                    FOREIGN KEY (cree_par_id)
                    REFERENCES app_user (id)
                    ON DELETE SET NULL,
                CONSTRAINT CHK_CONTRAT_STAGE_STATUT CHECK (
                    statut IN (
                        'BROUILLON', 'A_VALIDER', 'EN_SIGNATURE',
                        'BLOQUE', 'SIGNE', 'EN_COURS', 'TERMINE',
                        'ROMPU', 'ARCHIVE', 'ANNULE'
                    )
                )
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        SQL);

        // 2. Versions successives et instantanés contractuels.
        $this->addSql(<<<'SQL'
            CREATE TABLE contrat_stage_version (
                id INT AUTO_INCREMENT NOT NULL,
                contrat_id INT NOT NULL,
                numero_version INT NOT NULL,
                type_version VARCHAR(20) NOT NULL,
                motif_avenant LONGTEXT DEFAULT NULL,
                statut VARCHAR(30) NOT NULL DEFAULT 'BROUILLON',

                candidat_id INT NOT NULL,
                entreprise_id INT NOT NULL,

                entreprise_raison_sociale VARCHAR(180) NOT NULL,
                entreprise_sigle VARCHAR(80) DEFAULT NULL,
                entreprise_rccm VARCHAR(100) DEFAULT NULL,
                entreprise_secteur VARCHAR(160) DEFAULT NULL,
                entreprise_adresse VARCHAR(255) DEFAULT NULL,
                entreprise_ville VARCHAR(120) DEFAULT NULL,
                entreprise_telephone VARCHAR(30) DEFAULT NULL,
                entreprise_email VARCHAR(180) DEFAULT NULL,
                entreprise_representant_nom VARCHAR(160) DEFAULT NULL,
                entreprise_representant_fonction VARCHAR(120) DEFAULT NULL,
                entreprise_representant_telephone VARCHAR(30) DEFAULT NULL,
                entreprise_representant_email VARCHAR(180) DEFAULT NULL,

                stagiaire_nom VARCHAR(100) NOT NULL,
                stagiaire_prenoms VARCHAR(160) NOT NULL,
                stagiaire_sexe VARCHAR(10) DEFAULT NULL,
                stagiaire_telephone VARCHAR(30) NOT NULL,
                stagiaire_email VARCHAR(180) DEFAULT NULL,
                stagiaire_metier VARCHAR(80) NOT NULL,
                stagiaire_diplome VARCHAR(50) DEFAULT NULL,
                stagiaire_adresse VARCHAR(255) DEFAULT NULL,

                etablissement_id INT NOT NULL,
                etablissement_nom VARCHAR(180) NOT NULL,
                etablissement_ville VARCHAR(120) NOT NULL,
                etablissement_adresse VARCHAR(255) DEFAULT NULL,
                etablissement_telephone VARCHAR(30) DEFAULT NULL,
                etablissement_email VARCHAR(180) DEFAULT NULL,

                etablissement_signataire_type VARCHAR(30) NOT NULL,
                etablissement_signataire_user_id INT DEFAULT NULL,
                etablissement_signataire_nom VARCHAR(160) NOT NULL,
                etablissement_signataire_fonction VARCHAR(120) NOT NULL,
                etablissement_signataire_email VARCHAR(180) DEFAULT NULL,
                etablissement_signataire_telephone VARCHAR(30) DEFAULT NULL,

                metier_stage VARCHAR(160) NOT NULL,
                secteur_stage VARCHAR(160) DEFAULT NULL,
                poste_stage VARCHAR(180) NOT NULL,
                missions LONGTEXT NOT NULL,
                date_debut DATE NOT NULL,
                date_fin DATE NOT NULL,
                jours_travail VARCHAR(120) DEFAULT NULL,
                heure_debut TIME DEFAULT NULL,
                heure_fin TIME DEFAULT NULL,
                pause_minutes INT DEFAULT NULL,
                heures_hebdomadaires DECIMAL(5,2) DEFAULT NULL,

                maitre_stage_user_id INT DEFAULT NULL,
                maitre_stage_nom VARCHAR(160) DEFAULT NULL,
                maitre_stage_fonction VARCHAR(120) DEFAULT NULL,
                maitre_stage_telephone VARCHAR(30) DEFAULT NULL,
                referent_user_id INT DEFAULT NULL,
                referent_nom VARCHAR(160) DEFAULT NULL,

                salaire_reference DECIMAL(12,2) NOT NULL,
                reference_salaire VARCHAR(255) NOT NULL,
                taux_minimum DECIMAL(5,2) NOT NULL DEFAULT 50.00,
                seuil_indemnite DECIMAL(12,2) NOT NULL,
                indemnite_mensuelle DECIMAL(12,2) NOT NULL,
                indemnite_conforme TINYINT(1) NOT NULL DEFAULT 0,

                contrat_pdf_path VARCHAR(500) DEFAULT NULL,
                contrat_pdf_hash CHAR(64) DEFAULT NULL,

                scan_signe_path VARCHAR(500) DEFAULT NULL,
                scan_signe_hash CHAR(64) DEFAULT NULL,
                scan_depose_par_id INT DEFAULT NULL,
                scan_depose_le DATETIME DEFAULT NULL,
                scan_verifie TINYINT(1) NOT NULL DEFAULT 0,
                scan_verifie_par_id INT DEFAULT NULL,
                scan_verifie_le DATETIME DEFAULT NULL,

                pdf_final_path VARCHAR(500) DEFAULT NULL,
                pdf_final_hash CHAR(64) DEFAULT NULL,

                genere_le DATETIME DEFAULT NULL,
                signatures_terminees_le DATETIME DEFAULT NULL,
                finalise_le DATETIME DEFAULT NULL,

                cree_par_id INT DEFAULT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,

                PRIMARY KEY(id),
                UNIQUE INDEX UNIQ_CONTRAT_VERSION_NUMERO (contrat_id, numero_version),
                UNIQUE INDEX UNIQ_CONTRAT_VERSION_ID_CONTRAT (id, contrat_id),

                INDEX IDX_CONTRAT_VERSION_CANDIDAT (candidat_id),
                INDEX IDX_CONTRAT_VERSION_ENTREPRISE (entreprise_id),
                INDEX IDX_CONTRAT_VERSION_ETABLISSEMENT (etablissement_id),
                INDEX IDX_CONTRAT_VERSION_ETAB_USER (etablissement_signataire_user_id),
                INDEX IDX_CONTRAT_VERSION_MAITRE (maitre_stage_user_id),
                INDEX IDX_CONTRAT_VERSION_REFERENT (referent_user_id),
                INDEX IDX_CONTRAT_VERSION_CREATEUR (cree_par_id),
                INDEX IDX_CONTRAT_VERSION_SCAN_DEPOT (scan_depose_par_id),
                INDEX IDX_CONTRAT_VERSION_SCAN_VERIF (scan_verifie_par_id),

                CONSTRAINT FK_CONTRAT_VERSION_DOSSIER
                    FOREIGN KEY (contrat_id)
                    REFERENCES contrat_stage (id)
                    ON DELETE RESTRICT,

                CONSTRAINT FK_CONTRAT_VERSION_CANDIDAT
                    FOREIGN KEY (candidat_id)
                    REFERENCES candidat (id)
                    ON DELETE RESTRICT,

                CONSTRAINT FK_CONTRAT_VERSION_ENTREPRISE
                    FOREIGN KEY (entreprise_id)
                    REFERENCES entreprise_stage (id)
                    ON DELETE RESTRICT,

                CONSTRAINT FK_CONTRAT_VERSION_ETABLISSEMENT
                    FOREIGN KEY (etablissement_id)
                    REFERENCES etablissement_catalogue (id)
                    ON DELETE RESTRICT,

                CONSTRAINT FK_CONTRAT_VERSION_ETAB_USER
                    FOREIGN KEY (etablissement_signataire_user_id)
                    REFERENCES app_user (id)
                    ON DELETE SET NULL,

                CONSTRAINT FK_CONTRAT_VERSION_MAITRE
                    FOREIGN KEY (maitre_stage_user_id)
                    REFERENCES app_user (id)
                    ON DELETE SET NULL,

                CONSTRAINT FK_CONTRAT_VERSION_REFERENT
                    FOREIGN KEY (referent_user_id)
                    REFERENCES app_user (id)
                    ON DELETE SET NULL,

                CONSTRAINT FK_CONTRAT_VERSION_CREATEUR
                    FOREIGN KEY (cree_par_id)
                    REFERENCES app_user (id)
                    ON DELETE SET NULL,

                CONSTRAINT FK_CONTRAT_VERSION_SCAN_DEPOT
                    FOREIGN KEY (scan_depose_par_id)
                    REFERENCES app_user (id)
                    ON DELETE SET NULL,

                CONSTRAINT FK_CONTRAT_VERSION_SCAN_VERIF
                    FOREIGN KEY (scan_verifie_par_id)
                    REFERENCES app_user (id)
                    ON DELETE SET NULL,

                CONSTRAINT CHK_CONTRAT_VERSION_TYPE CHECK (
                    type_version IN ('INITIALE', 'AVENANT')
                ),

                CONSTRAINT CHK_CONTRAT_VERSION_STATUT CHECK (
                    statut IN (
                        'BROUILLON', 'A_VALIDER', 'EN_SIGNATURE',
                        'BLOQUE', 'SIGNE', 'REMPLACEE', 'ANNULEE'
                    )
                ),

                CONSTRAINT CHK_CONTRAT_VERSION_SIGNATAIRE CHECK (
                    etablissement_signataire_type IN (
                        'REFERENT_PARCOURS',
                        'RESPONSABLE_ETABLISSEMENT'
                    )
                ),

                CONSTRAINT CHK_CONTRAT_VERSION_DATES CHECK (
                    date_fin >= date_debut
                ),

                CONSTRAINT CHK_CONTRAT_VERSION_PAUSE CHECK (
                    pause_minutes IS NULL OR pause_minutes >= 0
                ),

                CONSTRAINT CHK_CONTRAT_VERSION_HEURES CHECK (
                    heures_hebdomadaires IS NULL
                    OR heures_hebdomadaires > 0
                ),

                CONSTRAINT CHK_CONTRAT_VERSION_TAUX CHECK (
                    taux_minimum > 0 AND taux_minimum <= 100
                ),

                CONSTRAINT CHK_CONTRAT_VERSION_MONTANTS CHECK (
                    salaire_reference >= 0
                    AND seuil_indemnite >= 0
                    AND indemnite_mensuelle >= 0
                ),

                CONSTRAINT CHK_CONTRAT_VERSION_SCAN CHECK (
                    scan_verifie IN (0, 1)
                ),

                CONSTRAINT CHK_CONTRAT_VERSION_CONFORME CHECK (
                    indemnite_conforme IN (0, 1)
                )
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        SQL);

        // La version courante doit appartenir au même dossier.
        $this->addSql(<<<'SQL'
            ALTER TABLE contrat_stage
            ADD CONSTRAINT FK_CONTRAT_STAGE_VERSION_COURANTE
            FOREIGN KEY (version_courante_id, id)
            REFERENCES contrat_stage_version (id, contrat_id)
            ON DELETE RESTRICT
        SQL);

        // 3. Trois lignes de signature par version, une par signataire.
        $this->addSql(<<<'SQL'
            CREATE TABLE contrat_stage_signature (
                id INT AUTO_INCREMENT NOT NULL,
                version_id INT NOT NULL,
                type_signataire VARCHAR(20) NOT NULL,
                user_id INT DEFAULT NULL,

                signataire_nom VARCHAR(160) NOT NULL,
                signataire_fonction VARCHAR(120) DEFAULT NULL,
                signataire_email VARCHAR(180) DEFAULT NULL,
                signataire_telephone VARCHAR(30) DEFAULT NULL,

                mode_signature VARCHAR(20) DEFAULT NULL,
                statut VARCHAR(30) NOT NULL DEFAULT 'EN_ATTENTE',
                signe_le DATETIME DEFAULT NULL,

                preuve_path VARCHAR(500) DEFAULT NULL,
                preuve_hash CHAR(64) DEFAULT NULL,
                reference_authentification VARCHAR(255) DEFAULT NULL,

                motif_refus LONGTEXT DEFAULT NULL,
                verifie_par_id INT DEFAULT NULL,
                verifie_le DATETIME DEFAULT NULL,

                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,

                PRIMARY KEY(id),
                UNIQUE INDEX UNIQ_CONTRAT_SIGNATURE_TYPE (version_id, type_signataire),
                INDEX IDX_CONTRAT_SIGNATURE_USER (user_id),
                INDEX IDX_CONTRAT_SIGNATURE_VERIF (verifie_par_id),

                CONSTRAINT FK_CONTRAT_SIGNATURE_VERSION
                    FOREIGN KEY (version_id)
                    REFERENCES contrat_stage_version (id)
                    ON DELETE RESTRICT,

                CONSTRAINT FK_CONTRAT_SIGNATURE_USER
                    FOREIGN KEY (user_id)
                    REFERENCES app_user (id)
                    ON DELETE SET NULL,

                CONSTRAINT FK_CONTRAT_SIGNATURE_VERIF
                    FOREIGN KEY (verifie_par_id)
                    REFERENCES app_user (id)
                    ON DELETE SET NULL,

                CONSTRAINT CHK_CONTRAT_SIGNATURE_TYPE CHECK (
                    type_signataire IN (
                        'ENTREPRISE', 'STAGIAIRE', 'ETABLISSEMENT'
                    )
                ),

                CONSTRAINT CHK_CONTRAT_SIGNATURE_MODE CHECK (
                    mode_signature IS NULL
                    OR mode_signature IN ('ELECTRONIQUE', 'MANUSCRITE')
                ),

                CONSTRAINT CHK_CONTRAT_SIGNATURE_STATUT CHECK (
                    statut IN (
                        'EN_ATTENTE', 'A_SIGNER',
                        'DEPOSEE_A_VERIFIER', 'SIGNEE', 'REFUSEE'
                    )
                )
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        SQL);

        // 4. Notifications préparées, autorisées et tracées.
        $this->addSql(<<<'SQL'
            CREATE TABLE contrat_stage_notification (
                id INT AUTO_INCREMENT NOT NULL,
                contrat_id INT NOT NULL,
                version_id INT DEFAULT NULL,
                destinataire_user_id INT DEFAULT NULL,

                destinataire_nom VARCHAR(160) NOT NULL,
                destinataire_email VARCHAR(180) DEFAULT NULL,
                destinataire_telephone VARCHAR(30) DEFAULT NULL,

                type_notification VARCHAR(40) NOT NULL,
                canal VARCHAR(20) NOT NULL,
                sujet VARCHAR(255) DEFAULT NULL,
                contenu LONGTEXT NOT NULL,

                statut VARCHAR(30) NOT NULL DEFAULT 'EN_ATTENTE_VALIDATION',
                cle_idempotence VARCHAR(120) NOT NULL,

                autorise_par_id INT DEFAULT NULL,
                autorise_le DATETIME DEFAULT NULL,
                envoye_le DATETIME DEFAULT NULL,
                erreur LONGTEXT DEFAULT NULL,

                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,

                PRIMARY KEY(id),
                UNIQUE INDEX UNIQ_CONTRAT_NOTIFICATION_CLE (cle_idempotence),
                INDEX IDX_CONTRAT_NOTIFICATION_DOSSIER (contrat_id),
                INDEX IDX_CONTRAT_NOTIFICATION_VERSION (version_id),
                INDEX IDX_CONTRAT_NOTIFICATION_DEST (destinataire_user_id),
                INDEX IDX_CONTRAT_NOTIFICATION_AUTORISEUR (autorise_par_id),
                INDEX IDX_CONTRAT_NOTIFICATION_STATUT (statut),

                CONSTRAINT FK_CONTRAT_NOTIFICATION_DOSSIER
                    FOREIGN KEY (contrat_id)
                    REFERENCES contrat_stage (id)
                    ON DELETE RESTRICT,

                CONSTRAINT FK_CONTRAT_NOTIFICATION_VERSION
                    FOREIGN KEY (version_id, contrat_id)
                    REFERENCES contrat_stage_version (id, contrat_id)
                    ON DELETE RESTRICT,

                CONSTRAINT FK_CONTRAT_NOTIFICATION_DEST
                    FOREIGN KEY (destinataire_user_id)
                    REFERENCES app_user (id)
                    ON DELETE SET NULL,

                CONSTRAINT FK_CONTRAT_NOTIFICATION_AUTORISEUR
                    FOREIGN KEY (autorise_par_id)
                    REFERENCES app_user (id)
                    ON DELETE SET NULL,

                CONSTRAINT CHK_CONTRAT_NOTIFICATION_TYPE CHECK (
                    type_notification IN (
                        'CONTRAT_A_SIGNER', 'RAPPEL_SIGNATURE',
                        'SIGNATURE_VALIDEE', 'SIGNATURE_REFUSEE',
                        'CONTRAT_SIGNE', 'CONTRAT_ANNULE',
                        'AVENANT_DISPONIBLE'
                    )
                ),

                CONSTRAINT CHK_CONTRAT_NOTIFICATION_CANAL CHECK (
                    canal IN ('EMAIL', 'SMS', 'INTERNE')
                ),

                CONSTRAINT CHK_CONTRAT_NOTIFICATION_STATUT CHECK (
                    statut IN (
                        'EN_ATTENTE_VALIDATION', 'AUTORISE',
                        'ENVOYE', 'ECHEC', 'ANNULE'
                    )
                ),

                CONSTRAINT CHK_CONTRAT_NOTIFICATION_EMAIL CHECK (
                    canal <> 'EMAIL'
                    OR destinataire_email IS NOT NULL
                ),

                CONSTRAINT CHK_CONTRAT_NOTIFICATION_SMS CHECK (
                    canal <> 'SMS'
                    OR destinataire_telephone IS NOT NULL
                )
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        SQL);

        // 5. Historique des opérations.
        $this->addSql(<<<'SQL'
            CREATE TABLE contrat_stage_historique (
                id BIGINT AUTO_INCREMENT NOT NULL,
                contrat_id INT NOT NULL,
                version_id INT DEFAULT NULL,
                user_id INT DEFAULT NULL,

                acteur_nom VARCHAR(160) DEFAULT NULL,
                acteur_fonction VARCHAR(120) DEFAULT NULL,

                action VARCHAR(80) NOT NULL,
                ancien_statut VARCHAR(30) DEFAULT NULL,
                nouveau_statut VARCHAR(30) DEFAULT NULL,
                description LONGTEXT DEFAULT NULL,
                donnees_json LONGTEXT DEFAULT NULL,
                adresse_ip VARCHAR(45) DEFAULT NULL,

                created_at DATETIME NOT NULL,

                PRIMARY KEY(id),
                INDEX IDX_CONTRAT_HISTORIQUE_DOSSIER (contrat_id),
                INDEX IDX_CONTRAT_HISTORIQUE_VERSION (version_id),
                INDEX IDX_CONTRAT_HISTORIQUE_USER (user_id),
                INDEX IDX_CONTRAT_HISTORIQUE_DATE (created_at),

                CONSTRAINT FK_CONTRAT_HISTORIQUE_DOSSIER
                    FOREIGN KEY (contrat_id)
                    REFERENCES contrat_stage (id)
                    ON DELETE RESTRICT,

                CONSTRAINT FK_CONTRAT_HISTORIQUE_VERSION
                    FOREIGN KEY (version_id, contrat_id)
                    REFERENCES contrat_stage_version (id, contrat_id)
                    ON DELETE RESTRICT,

                CONSTRAINT FK_CONTRAT_HISTORIQUE_USER
                    FOREIGN KEY (user_id)
                    REFERENCES app_user (id)
                    ON DELETE SET NULL,

                CONSTRAINT CHK_CONTRAT_HISTORIQUE_JSON CHECK (
                    donnees_json IS NULL OR JSON_VALID(donnees_json)
                )
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        SQL);
    }

    public function down(Schema $schema): void
    {
        // Suppression dans l'ordre inverse des dépendances.
        $this->addSql('DROP TABLE contrat_stage_historique');
        $this->addSql('DROP TABLE contrat_stage_notification');
        $this->addSql('DROP TABLE contrat_stage_signature');

        $this->addSql(<<<'SQL'
            ALTER TABLE contrat_stage
            DROP FOREIGN KEY FK_CONTRAT_STAGE_VERSION_COURANTE
        SQL);

        $this->addSql('DROP TABLE contrat_stage_version');
        $this->addSql('DROP TABLE contrat_stage');
    }
}
