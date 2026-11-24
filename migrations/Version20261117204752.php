<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261117204752 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(180) NOT NULL, message LONGTEXT NOT NULL, type VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL, read_at DATETIME DEFAULT NULL, archived_at DATETIME DEFAULT NULL, deduplicationKey VARCHAR(120) DEFAULT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_BF5476CAD2DAF730 (deduplicationKey), INDEX IDX_NOTIFICATION_USER_DATE (user_id, created_at), INDEX IDX_NOTIFICATION_USER_READ (user_id, read_at), INDEX IDX_BF5476CAA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE notification_envoi (id INT AUTO_INCREMENT NOT NULL, canal VARCHAR(20) NOT NULL, destinataire VARCHAR(40) NOT NULL, prestataire VARCHAR(80) DEFAULT NULL, statut VARCHAR(30) NOT NULL, referenceExterne VARCHAR(180) DEFAULT NULL, erreur LONGTEXT DEFAULT NULL, dateTentative DATETIME NOT NULL, dateEnvoi DATETIME DEFAULT NULL, notification_id INT NOT NULL, INDEX IDX_NOTIF_ENVOI_NOTIFICATION (notification_id), INDEX IDX_NOTIF_ENVOI_STATUT (statut), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAA76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE notification_envoi ADD CONSTRAINT FK_CCAD09FEF1A9D84 FOREIGN KEY (notification_id) REFERENCES notification (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAA76ED395');
        $this->addSql('ALTER TABLE notification_envoi DROP FOREIGN KEY FK_CCAD09FEF1A9D84');
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP TABLE notification_envoi');
    }
}
