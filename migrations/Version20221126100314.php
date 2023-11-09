<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221126100314 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE resource ADD name_en LONGTEXT NOT NULL, ADD name_fr LONGTEXT DEFAULT NULL, ADD description_en LONGTEXT NOT NULL, ADD description_fr LONGTEXT DEFAULT NULL, DROP name, DROP description');
        $this->addSql('ALTER TABLE phone_number CHANGE phone phone LONGTEXT DEFAULT NULL');
        $this->addSql('CREATE FULLTEXT INDEX search ON resource (name_en, name_fr, description_en, description_fr)');
    }
}
