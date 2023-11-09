<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230604145337 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("create index name_fr on resource (name_fr)");
        $this->addSql("create index computed_canonical_record_id on resource (computed_canonical_record_id)");
        $this->addSql("update resource set computed_canonical_record_id = null where computed_canonical_record_id = ''");
    }
}
