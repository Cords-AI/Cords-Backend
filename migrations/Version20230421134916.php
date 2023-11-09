<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230421134916 extends AbstractMigration
{

    public function up(Schema $schema): void
    {
        $this->addSql(
            "ALTER TABLE resource DROP hours, RENAME COLUMN email TO email_en, 
                    ADD email_fr VARCHAR(255), MODIFY modified_date DATETIME NULL"
        );
        $this->addSql(
            "ALTER TABLE address ADD type VARCHAR(255), ADD mailing_attention_name VARCHAR(350)"
        );
        $this->addSql(
            "ALTER TABLE address DROP FOREIGN KEY FK_D4E6F8189329D25, DROP INDEX UNIQ_D4E6F8189329D25"
        );
    }
}
