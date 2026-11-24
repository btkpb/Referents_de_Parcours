<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261122180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute le rattachement des tentatives de notification aux entretiens et étend le destinataire.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE notification_envoi
             MODIFY destinataire VARCHAR(254) NOT NULL'
        );

        $this->addSql(
            'ALTER TABLE notification_envoi
             ADD entretien_id INT DEFAULT NULL'
        );

        $this->addSql(
            'CREATE INDEX IDX_NOTIF_ENVOI_ENTRETIEN
             ON notification_envoi (entretien_id)'
        );

        $this->addSql(
            'ALTER TABLE notification_envoi
             ADD CONSTRAINT FK_NOTIF_ENVOI_ENTRETIEN
             FOREIGN KEY (entretien_id)
             REFERENCES entretien (id)
             ON DELETE CASCADE'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE notification_envoi
             DROP FOREIGN KEY FK_NOTIF_ENVOI_ENTRETIEN'
        );

        $this->addSql(
            'DROP INDEX IDX_NOTIF_ENVOI_ENTRETIEN
             ON notification_envoi'
        );

        $this->addSql(
            'ALTER TABLE notification_envoi
             DROP entretien_id'
        );

        $this->addSql(
            'ALTER TABLE notification_envoi
             MODIFY destinataire VARCHAR(40) NOT NULL'
        );
    }
}