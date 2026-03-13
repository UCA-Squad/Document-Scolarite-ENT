<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250204150239 extends AbstractMigration
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

        if (!$table->hasColumn('username')) {
            $this->addSql('ALTER TABLE user ADD username VARCHAR(25) NOT NULL');
        }

        if (!$table->hasIndex('UNIQ_8D93D649F85E0677')) {
            $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649F85E0677 ON user (username)');
        }
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('user')) {
            return;
        }

        $table = $schema->getTable('user');

        if ($table->hasIndex('UNIQ_8D93D649F85E0677')) {
            $this->addSql('DROP INDEX UNIQ_8D93D649F85E0677 ON user');
        }

        if ($table->hasColumn('username')) {
            $this->addSql('ALTER TABLE user DROP username');
        }
    }
}
