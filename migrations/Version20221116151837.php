<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221116151837 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE address DROP FOREIGN KEY FK_D4E6F8189329D25');
        $this->addSql('ALTER TABLE address ADD CONSTRAINT FK_D4E6F8189329D25 FOREIGN KEY (resource_id) REFERENCES resource (id)');
        $this->addSql('ALTER TABLE inquiry DROP FOREIGN KEY FK_5A3903F089329D25');
        $this->addSql('ALTER TABLE inquiry ADD CONSTRAINT FK_5A3903F089329D25 FOREIGN KEY (resource_id) REFERENCES resource (id)');
        $this->addSql('ALTER TABLE phone_number DROP FOREIGN KEY FK_6B01BC5B89329D25');
        $this->addSql('ALTER TABLE phone_number ADD CONSTRAINT FK_6B01BC5B89329D25 FOREIGN KEY (resource_id) REFERENCES resource (id)');
        $this->addSql('ALTER TABLE service_area DROP FOREIGN KEY FK_19D7898489329D25');
        $this->addSql('ALTER TABLE service_area ADD CONSTRAINT FK_19D7898489329D25 FOREIGN KEY (resource_id) REFERENCES resource (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE address DROP FOREIGN KEY FK_D4E6F8189329D25');
        $this->addSql('ALTER TABLE address ADD CONSTRAINT FK_D4E6F8189329D25 FOREIGN KEY (resource_id) REFERENCES resource (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE service_area DROP FOREIGN KEY FK_19D7898489329D25');
        $this->addSql('ALTER TABLE service_area ADD CONSTRAINT FK_19D7898489329D25 FOREIGN KEY (resource_id) REFERENCES resource (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE phone_number DROP FOREIGN KEY FK_6B01BC5B89329D25');
        $this->addSql('ALTER TABLE phone_number ADD CONSTRAINT FK_6B01BC5B89329D25 FOREIGN KEY (resource_id) REFERENCES resource (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE inquiry DROP FOREIGN KEY FK_5A3903F089329D25');
        $this->addSql('ALTER TABLE inquiry ADD CONSTRAINT FK_5A3903F089329D25 FOREIGN KEY (resource_id) REFERENCES resource (id) ON UPDATE CASCADE ON DELETE CASCADE');
    }
}
