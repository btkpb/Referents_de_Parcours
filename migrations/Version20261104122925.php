<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261104122925 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE evaluation ADD scoreIdentification DOUBLE PRECISION NOT NULL, ADD scoreFreins DOUBLE PRECISION NOT NULL, ADD scoreOrganisation DOUBLE PRECISION NOT NULL, ADD scoreAnticipation DOUBLE PRECISION NOT NULL, ADD scoreRelations DOUBLE PRECISION NOT NULL, ADD scorePremiereImpression DOUBLE PRECISION NOT NULL, ADD scoreExpressionOrale DOUBLE PRECISION NOT NULL, DROP scoreTechnique, DROP scoreCommunication, DROP scoreSocial, DROP scoreMobilite, DROP scoreFinances, DROP scoreLogement, DROP scoreSante');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE Evaluation ADD scoreTechnique DOUBLE PRECISION NOT NULL, ADD scoreCommunication DOUBLE PRECISION NOT NULL, ADD scoreSocial DOUBLE PRECISION NOT NULL, ADD scoreMobilite DOUBLE PRECISION NOT NULL, ADD scoreFinances DOUBLE PRECISION NOT NULL, ADD scoreLogement DOUBLE PRECISION NOT NULL, ADD scoreSante DOUBLE PRECISION NOT NULL, DROP scoreIdentification, DROP scoreFreins, DROP scoreOrganisation, DROP scoreAnticipation, DROP scoreRelations, DROP scorePremiereImpression, DROP scoreExpressionOrale');
    }
}
