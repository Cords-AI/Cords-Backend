<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230123151143 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE resource ADD computed_canonical_record_id VARCHAR(36)');

    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE resource DROP computed_canonical_record_id');

    }
}
