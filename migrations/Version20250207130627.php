<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250207130627 extends AbstractMigration
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

        $table = $schema->getTable('user');

        if (!$table->hasColumn('prenom')) {
            $this->addSql('ALTER TABLE user ADD prenom VARCHAR(255) NOT NULL');
        }

        if (!$table->hasColumn('nom')) {
            $this->addSql('ALTER TABLE user ADD nom VARCHAR(255) NOT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('user')) {
            return;
        }

        $table = $schema->getTable('user');

        if ($table->hasColumn('prenom')) {
            $this->addSql('ALTER TABLE user DROP prenom');
        }

        if ($table->hasColumn('nom')) {
            $this->addSql('ALTER TABLE user DROP nom');
        }
    }
}
