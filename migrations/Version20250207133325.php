<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250207133325 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('user')) {
            return;
        }

        if (!$schema->getTable('user')->hasColumn('composante')) {
            $this->addSql('ALTER TABLE user ADD composante VARCHAR(25) NOT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('user')) {
            return;
        }

        if ($schema->getTable('user')->hasColumn('composante')) {
            $this->addSql('ALTER TABLE user DROP composante');
        }
    }
}
