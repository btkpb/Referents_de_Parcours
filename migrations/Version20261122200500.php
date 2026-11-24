<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261122200500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crée l’historique des e-mails d’information envoyés aux candidats et apprentis';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE email_information_envoi (
                id INT AUTO_INCREMENT NOT NULL,
                audience VARCHAR(20) NOT NULL,
                destinataire_id INT DEFAULT NULL,
                nom_destinataire VARCHAR(255) NOT NULL,
                email_destinataire VARCHAR(255) NOT NULL,
                sujet VARCHAR(255) NOT NULL,
                contenu LONGTEXT NOT NULL,
                statut VARCHAR(20) NOT NULL,
                erreur LONGTEXT DEFAULT NULL,
                expediteur VARCHAR(255) NOT NULL,
                nom_expediteur VARCHAR(120) NOT NULL,
                date_tentative DATETIME NOT NULL,
                date_envoi DATETIME DEFAULT NULL,
                INDEX IDX_EMAIL_INFO_AUDIENCE_DATE (audience, date_tentative),
                INDEX IDX_EMAIL_INFO_STATUT (statut),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4
              COLLATE `utf8mb4_unicode_ci`
              ENGINE = InnoDB'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE email_information_envoi');
    }
}