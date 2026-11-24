<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261106140424 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les informations de décision collective et d’arbitrage du Gestionnaire pour les admissions E2C.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE admission
                ADD score_moyen DOUBLE PRECISION DEFAULT NULL,
                ADD avis_jury VARCHAR(30) NOT NULL DEFAULT 'EN_ATTENTE',
                ADD arbitrage_gestionnaire VARCHAR(30) NOT NULL DEFAULT 'EN_ATTENTE',
                ADD observations_arbitrage LONGTEXT DEFAULT NULL,
                ADD date_arbitrage DATETIME DEFAULT NULL,
                ADD arbitre_id INT DEFAULT NULL
        ");

        $this->addSql("CREATE INDEX IDX_ADMISSION_ARBITRE ON admission (arbitre_id)");

        $this->addSql("
            ALTER TABLE admission
            ADD CONSTRAINT FK_ADMISSION_ARBITRE
            FOREIGN KEY (arbitre_id) REFERENCES app_user (id)
            ON DELETE SET NULL
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE admission DROP FOREIGN KEY FK_ADMISSION_ARBITRE");
        $this->addSql("DROP INDEX IDX_ADMISSION_ARBITRE ON admission");

        $this->addSql("
            ALTER TABLE admission
                DROP score_moyen,
                DROP avis_jury,
                DROP arbitrage_gestionnaire,
                DROP observations_arbitrage,
                DROP date_arbitrage,
                DROP arbitre_id
        ");
    }
}