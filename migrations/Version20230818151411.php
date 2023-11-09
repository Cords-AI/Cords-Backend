<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230818151411 extends AbstractMigration
{

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE related_topics
                          (id INT AUTO_INCREMENT NOT NULL,
                          topics JSON,
                          term_pair VARCHAR(750) AS (CAST(topics AS CHAR)) STORED,
                          weight INT , 
                          PRIMARY KEY(id), UNIQUE (term_pair))
                          DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
    }
}
