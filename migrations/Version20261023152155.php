<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261023152155 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE app_user ADD candidat_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD CONSTRAINT FK_88BDF3E98D0EB82 FOREIGN KEY (candidat_id) REFERENCES Candidat (id) ON DELETE SET NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_88BDF3E98D0EB82 ON app_user (candidat_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE app_user DROP FOREIGN KEY FK_88BDF3E98D0EB82');
        $this->addSql('DROP INDEX UNIQ_88BDF3E98D0EB82 ON app_user');
        $this->addSql('ALTER TABLE app_user DROP candidat_id');
    }
}
