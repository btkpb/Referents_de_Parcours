<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261104151626 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE entretien_jury (entretien_id INT NOT NULL, jury_id INT NOT NULL, INDEX IDX_9AB5C3EB548DCEA2 (entretien_id), INDEX IDX_9AB5C3EBE560103C (jury_id), PRIMARY KEY (entretien_id, jury_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE entretien_jury ADD CONSTRAINT FK_9AB5C3EB548DCEA2 FOREIGN KEY (entretien_id) REFERENCES entretien (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE entretien_jury ADD CONSTRAINT FK_9AB5C3EBE560103C FOREIGN KEY (jury_id) REFERENCES app_user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE evaluation DROP FOREIGN KEY `FK_5C7EA6A5548DCEA2`');
        $this->addSql('ALTER TABLE evaluation DROP INDEX UNIQ_5C7EA6A5548DCEA2, ADD INDEX IDX_1323A575548DCEA2 (entretien_id)');
        $this->addSql('ALTER TABLE evaluation ADD jury_id INT NOT NULL, CHANGE entretien_id entretien_id INT NOT NULL');
        $this->addSql('ALTER TABLE evaluation ADD CONSTRAINT FK_1323A575548DCEA2 FOREIGN KEY (entretien_id) REFERENCES entretien (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE evaluation ADD CONSTRAINT FK_1323A575E560103C FOREIGN KEY (jury_id) REFERENCES app_user (id) ON DELETE RESTRICT');
        $this->addSql('CREATE INDEX IDX_1323A575E560103C ON evaluation (jury_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_evaluation_entretien_jury ON evaluation (entretien_id, jury_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE entretien_jury DROP FOREIGN KEY FK_9AB5C3EB548DCEA2');
        $this->addSql('ALTER TABLE entretien_jury DROP FOREIGN KEY FK_9AB5C3EBE560103C');
        $this->addSql('DROP TABLE entretien_jury');

        $this->addSql('DROP INDEX uniq_evaluation_entretien_jury ON evaluation');

        $this->addSql('ALTER TABLE evaluation DROP FOREIGN KEY FK_1323A575548DCEA2');
        $this->addSql('ALTER TABLE evaluation DROP FOREIGN KEY FK_1323A575E560103C');

        $this->addSql('DROP INDEX IDX_1323A575E560103C ON evaluation');

        $this->addSql('ALTER TABLE evaluation DROP INDEX IDX_1323A575548DCEA2, ADD UNIQUE INDEX UNIQ_5C7EA6A5548DCEA2 (entretien_id)');

        $this->addSql('ALTER TABLE evaluation DROP jury_id, CHANGE entretien_id entretien_id INT DEFAULT NULL');

        $this->addSql('ALTER TABLE evaluation ADD CONSTRAINT FK_5C7EA6A5548DCEA2 FOREIGN KEY (entretien_id) REFERENCES entretien (id)');
    }
}
