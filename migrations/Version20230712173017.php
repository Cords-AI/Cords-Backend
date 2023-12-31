<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230712173017 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE resource SET delivery = null");
        $this->addSql("TRUNCATE delivery_province");
    }
}

