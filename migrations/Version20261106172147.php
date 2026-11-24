<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261106172147 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add candidate identity photo after admission.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE candidat ADD photoIdentite VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE candidat DROP photoIdentite');
    }
}