<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20221120013956 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE resource CHANGE email email LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE resource CHANGE website website LONGTEXT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE resource CHANGE email email VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE resource CHANGE website website VARCHAR(255) NOT NULL');
    }
}
