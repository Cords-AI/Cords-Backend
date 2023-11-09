<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230701182401 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $sql = "
            CREATE TABLE `resource_source` (
              `id` int NOT NULL AUTO_INCREMENT,
              `partner` varchar(255),
              `source` varchar(255),
              `partner_id` varchar(255),
              `document` JSON,
              PRIMARY KEY (`id`)
            );
        ";
        $sql .= "alter table resource_source add constraint resource_source_pk unique (partner, source, partner_id);";
        $this->addSql($sql);
    }
}
