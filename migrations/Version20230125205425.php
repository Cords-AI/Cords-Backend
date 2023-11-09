<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230125205425 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE resource MODIFY COLUMN id211 VARCHAR(255), ADD partner TEXT, ADD body_en JSON, ADD body_fr JSON, ADD website_fr LONGTEXT');
        $this->addSql('ALTER TABLE resource RENAME COLUMN id211 TO partner_id, RENAME COLUMN website TO website_en');

    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE resource DROP COLUMN partner_id , DROP COLUMN partner, DROP body_eN, DROP body_fr, DROP website_fr');
        $this->addSql('ALTER TABLE resource ADD COLUMN id211 int, RENAME COLUMN website_en TO website');

    }
}
