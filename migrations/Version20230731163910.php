<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230731163910 extends AbstractMigration
{

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE resource ADD date_of_first_import DATETIME, ADD date_of_latest_import DATETIME");
        $dateOfFirstImport = (new \DateTime())->format("Y-m-d H:i:s");
        $dateOfFirstImport = "\"{$dateOfFirstImport}\"";
        $dateOfLatestImport = (new \DateTime())->format("Y-m-d H:i:s");
        $dateOfLatestImport = "\"{$dateOfLatestImport}\"";
        $this->addSql("UPDATE resource SET date_of_first_import = {$dateOfFirstImport}, date_of_latest_import = {$dateOfLatestImport}");
    }
}
