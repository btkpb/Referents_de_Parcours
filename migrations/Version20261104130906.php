<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261104130906 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création des quotas de métiers par campagne de recrutement.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE quota_metier (
                id INT AUTO_INCREMENT NOT NULL,
                quota INT NOT NULL,
                campagne_id INT NOT NULL,
                metier_id INT NOT NULL,
                UNIQUE INDEX uniq_quota_campagne_metier (campagne_id, metier_id),
                INDEX IDX_EF52AD3516227374 (campagne_id),
                INDEX IDX_EF52AD35ED16FA20 (metier_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4'
        );

        $this->addSql(
            'ALTER TABLE quota_metier
             ADD CONSTRAINT FK_QUOTA_CAMPAGNE
             FOREIGN KEY (campagne_id) REFERENCES campagne (id)
             ON DELETE CASCADE'
        );

        $this->addSql(
            'ALTER TABLE quota_metier
             ADD CONSTRAINT FK_QUOTA_METIER
             FOREIGN KEY (metier_id) REFERENCES metier_catalogue (id)
             ON DELETE RESTRICT'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE quota_metier DROP FOREIGN KEY FK_QUOTA_CAMPAGNE'
        );

        $this->addSql(
            'ALTER TABLE quota_metier DROP FOREIGN KEY FK_QUOTA_METIER'
        );

        $this->addSql('DROP TABLE quota_metier');
    }
}
