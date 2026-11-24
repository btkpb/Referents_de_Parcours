<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261110100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création de la table des affectations des responsables des apprentis.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE affectation_responsable_apprenti (
            id INT AUTO_INCREMENT NOT NULL,
            type_responsabilite VARCHAR(30) NOT NULL,
            dateDebut DATE NOT NULL,
            dateFin DATE DEFAULT NULL,
            statut VARCHAR(20) NOT NULL,
            motifFin LONGTEXT DEFAULT NULL,
            createdAt DATETIME NOT NULL,
            updatedAt DATETIME NOT NULL,
            candidat_id INT NOT NULL,
            responsable_id INT NOT NULL,
            affectePar_id INT NOT NULL,
            INDEX idx_affect_resp_candidat_type_statut (candidat_id, type_responsabilite, statut),
            INDEX idx_affect_resp_responsable (responsable_id),
            INDEX IDX_96CEC6B78D0EB82 (candidat_id),
            INDEX IDX_96CEC6B7F0417C88 (affectePar_id),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('ALTER TABLE affectation_responsable_apprenti
            ADD CONSTRAINT FK_96CEC6B78D0EB82
            FOREIGN KEY (candidat_id) REFERENCES candidat (id) ON DELETE RESTRICT');

        $this->addSql('ALTER TABLE affectation_responsable_apprenti
            ADD CONSTRAINT FK_96CEC6B753C59D72
            FOREIGN KEY (responsable_id) REFERENCES app_user (id) ON DELETE RESTRICT');

        $this->addSql('ALTER TABLE affectation_responsable_apprenti
            ADD CONSTRAINT FK_96CEC6B7F0417C88
            FOREIGN KEY (affectePar_id) REFERENCES app_user (id) ON DELETE RESTRICT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE affectation_responsable_apprenti
            DROP FOREIGN KEY FK_96CEC6B78D0EB82');

        $this->addSql('ALTER TABLE affectation_responsable_apprenti
            DROP FOREIGN KEY FK_96CEC6B753C59D72');

        $this->addSql('ALTER TABLE affectation_responsable_apprenti
            DROP FOREIGN KEY FK_96CEC6B7F0417C88');

        $this->addSql('DROP TABLE affectation_responsable_apprenti');
    }
}