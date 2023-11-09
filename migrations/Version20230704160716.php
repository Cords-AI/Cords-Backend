<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
final class Version20230704160716 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE delivery_province (id INT AUTO_INCREMENT NOT NULL, 
            resource_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', abbreviated_name VARCHAR(255) NOT NULL,
            full_name VARCHAR(255) NOT NULL, 
            INDEX IDX_2BBB356789329D25 (resource_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 
            COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
            ALTER TABLE resource DROP delivery_province;
            ALTER TABLE delivery_province ADD CONSTRAINT FK_2BBB356789329D25 FOREIGN KEY (resource_id) REFERENCES resource (id)');
    }
}
