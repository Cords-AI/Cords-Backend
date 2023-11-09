<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;


final class Version20230810152910 extends AbstractMigration
{

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE resource ADD topic VARCHAR(500)");
        $this->addSql("UPDATE resource SET topic = 'Mentoring' WHERE partner = 'mentor';
                          UPDATE resource SET topic = 'Benefit' WHERE partner = 'prosper';
                          UPDATE resource SET topic = 'Employment' WHERE partner = 'magnet';
                          CREATE TEMPORARY TABLE tempResource AS
                          SELECT id, SUBSTRING_INDEX((JSON_UNQUOTE(COALESCE(json_extract(resource.taxonomy_en, '$.taxonomyTerm'), JSON_EXTRACT(resource.taxonomy_fr, '$.taxonomyTerm')))), ';', 1) AS topic
                          FROM resource WHERE partner = '211';");
        $this->addSql("UPDATE resource SET topic = (SELECT topic from tempResource WHERE tempResource.id = resource.id) WHERE resource.partner = '211';
                       DROP TEMPORARY TABLE tempResource;");
    }
}
