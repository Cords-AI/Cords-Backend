<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230603175647 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table address add constraint address_resource_id_fk foreign key (resource_id) references resource (id)");
    }
}
