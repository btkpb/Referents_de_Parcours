<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261122190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creation de la table convocation_envoi pour historiser les envois de convocations';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE convocation_envoi (id INT AUTO_INCREMENT NOT NULL, canal VARCHAR(20) NOT NULL, destinataire VARCHAR(254) NOT NULL, statut VARCHAR(30) NOT NULL, referenceExterne VARCHAR(180) DEFAULT NULL, erreur LONGTEXT DEFAULT NULL, dateTentative DATETIME NOT NULL, dateEnvoi DATETIME DEFAULT NULL, entretien_id INT NOT NULL, candidat_id INT NOT NULL, INDEX IDX_CONVOCATION_ENVOI_ENTRETIEN (entretien_id), INDEX IDX_CONVOCATION_ENVOI_CANDIDAT (candidat_id), INDEX IDX_CONVOCATION_ENVOI_STATUT (statut), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE convocation_envoi ADD CONSTRAINT FK_286AA56F548DCEA2 FOREIGN KEY (entretien_id) REFERENCES Entretien (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE convocation_envoi ADD CONSTRAINT FK_286AA56F8D0EB82 FOREIGN KEY (candidat_id) REFERENCES candidat (id) ON DELETE RESTRICT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE convocation_envoi DROP FOREIGN KEY FK_286AA56F548DCEA2');
        $this->addSql('ALTER TABLE convocation_envoi DROP FOREIGN KEY FK_286AA56F8D0EB82');
        $this->addSql('DROP TABLE convocation_envoi');
    }
}
