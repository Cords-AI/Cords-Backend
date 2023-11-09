<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221109160739 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE program ADD id211 INT NOT NULL, ADD name JSON NOT NULL, ADD description JSON NOT NULL, ADD website VARCHAR(255) NOT NULL, ADD email VARCHAR(255) NOT NULL, ADD hours LONGTEXT NOT NULL, ADD modified_date DATETIME NOT NULL, ADD created_date DATETIME NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_92ED77845628B403 ON program (id211)');

        $this->addSql('CREATE TABLE resource (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', id211 INT NOT NULL, name JSON NOT NULL, description JSON NOT NULL, website VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, hours LONGTEXT NOT NULL, modified_date DATETIME NOT NULL, created_date DATETIME NOT NULL, UNIQUE INDEX UNIQ_BC91F4165628B403 (id211), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('DROP TABLE program');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_92ED77845628B403 ON program');
        $this->addSql('ALTER TABLE program DROP id211, DROP name, DROP description, DROP website, DROP email, DROP hours, DROP modified_date, DROP created_date');

        $this->addSql('CREATE TABLE program (id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:uuid)\', id211 INT NOT NULL, name JSON NOT NULL, description JSON NOT NULL, website VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, email VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, hours LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, modified_date DATETIME NOT NULL, created_date DATETIME NOT NULL, UNIQUE INDEX UNIQ_92ED77845628B403 (id211), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('DROP TABLE resource');
    }
}
