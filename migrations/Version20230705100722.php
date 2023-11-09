<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230705100722 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table inquiry drop foreign key FK_5A3903F089329D25");
    }
}
