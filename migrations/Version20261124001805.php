<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261124001805 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE admission DROP FOREIGN KEY `FK_ADMISSION_LOT_INTEGRATION`');
        $this->addSql('DROP INDEX idx_admission_lot_integration ON admission');
        $this->addSql('CREATE INDEX IDX_764A80E9B2D40390 ON admission (lot_integration_externe_id)');
        $this->addSql('ALTER TABLE admission ADD CONSTRAINT `FK_ADMISSION_LOT_INTEGRATION` FOREIGN KEY (lot_integration_externe_id) REFERENCES import_beneficiaire_lot (id)');
        $this->addSql('ALTER TABLE beneficiaire_externe DROP FOREIGN KEY `FK_BENEF_EXT_CANDIDAT`');
        $this->addSql('ALTER TABLE beneficiaire_externe DROP FOREIGN KEY `FK_BENEF_EXT_VALIDATEUR`');
        $this->addSql('ALTER TABLE beneficiaire_externe CHANGE observations_validation observations_validation LONGTEXT DEFAULT NULL, CHANGE experience experience LONGTEXT DEFAULT NULL');
        $this->addSql('DROP INDEX uniq_benef_ext_candidat ON beneficiaire_externe');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BE549D848D0EB82 ON beneficiaire_externe (candidat_id)');
        $this->addSql('DROP INDEX idx_benef_ext_validateur ON beneficiaire_externe');
        $this->addSql('CREATE INDEX IDX_BE549D846AF12ED9 ON beneficiaire_externe (valide_par_id)');
        $this->addSql('ALTER TABLE beneficiaire_externe ADD CONSTRAINT `FK_BENEF_EXT_CANDIDAT` FOREIGN KEY (candidat_id) REFERENCES candidat (id)');
        $this->addSql('ALTER TABLE beneficiaire_externe ADD CONSTRAINT `FK_BENEF_EXT_VALIDATEUR` FOREIGN KEY (valide_par_id) REFERENCES app_user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE import_beneficiaire_lot DROP FOREIGN KEY `FK_IMPORT_LOT_IMPORTATEUR`');
        $this->addSql('DROP INDEX idx_import_lot_importateur ON import_beneficiaire_lot');
        $this->addSql('CREATE INDEX IDX_A8FA325EB8AA8770 ON import_beneficiaire_lot (importe_par_id)');
        $this->addSql('ALTER TABLE import_beneficiaire_lot ADD CONSTRAINT `FK_IMPORT_LOT_IMPORTATEUR` FOREIGN KEY (importe_par_id) REFERENCES app_user (id)');
    }

    public function down(Schema $schema): void
    {
        // Restaurer la contrainte et l'index d'origine de admission.
        $this->addSql('ALTER TABLE admission DROP FOREIGN KEY FK_ADMISSION_LOT_INTEGRATION');
        $this->addSql('DROP INDEX IDX_764A80E9B2D40390 ON admission');
        $this->addSql('CREATE INDEX IDX_ADMISSION_LOT_INTEGRATION ON admission (lot_integration_externe_id)');
        $this->addSql('ALTER TABLE admission ADD CONSTRAINT FK_ADMISSION_LOT_INTEGRATION FOREIGN KEY (lot_integration_externe_id) REFERENCES import_beneficiaire_lot (id) ON DELETE RESTRICT');

        // Restaurer les contraintes et index de beneficiaire_externe.
        $this->addSql('ALTER TABLE beneficiaire_externe DROP FOREIGN KEY FK_BENEF_EXT_CANDIDAT');
        $this->addSql('ALTER TABLE beneficiaire_externe DROP FOREIGN KEY FK_BENEF_EXT_VALIDATEUR');
        $this->addSql('ALTER TABLE beneficiaire_externe CHANGE experience experience TEXT DEFAULT NULL, CHANGE observations_validation observations_validation TEXT DEFAULT NULL');
        $this->addSql('DROP INDEX IDX_BE549D846AF12ED9 ON beneficiaire_externe');
        $this->addSql('CREATE INDEX IDX_BENEF_EXT_VALIDATEUR ON beneficiaire_externe (valide_par_id)');
        $this->addSql('DROP INDEX UNIQ_BE549D848D0EB82 ON beneficiaire_externe');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BENEF_EXT_CANDIDAT ON beneficiaire_externe (candidat_id)');
        $this->addSql('ALTER TABLE beneficiaire_externe ADD CONSTRAINT FK_BENEF_EXT_CANDIDAT FOREIGN KEY (candidat_id) REFERENCES candidat (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE beneficiaire_externe ADD CONSTRAINT FK_BENEF_EXT_VALIDATEUR FOREIGN KEY (valide_par_id) REFERENCES app_user (id) ON DELETE SET NULL');

        // Restaurer la contrainte et l'index d'origine de import_beneficiaire_lot.
        $this->addSql('ALTER TABLE import_beneficiaire_lot DROP FOREIGN KEY FK_IMPORT_LOT_IMPORTATEUR');
        $this->addSql('DROP INDEX IDX_A8FA325EB8AA8770 ON import_beneficiaire_lot');
        $this->addSql('CREATE INDEX IDX_IMPORT_LOT_IMPORTATEUR ON import_beneficiaire_lot (importe_par_id)');
        $this->addSql('ALTER TABLE import_beneficiaire_lot ADD CONSTRAINT FK_IMPORT_LOT_IMPORTATEUR FOREIGN KEY (importe_par_id) REFERENCES app_user (id) ON DELETE RESTRICT');
    }
}
