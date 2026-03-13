<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250206102134 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('group')) {
            $this->addSql('CREATE TABLE `group` (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        }

        if (!$schema->hasTable('user_group')) {
            $this->addSql('CREATE TABLE user_group (id INT AUTO_INCREMENT NOT NULL, groupe_id INT NOT NULL, username VARCHAR(25) NOT NULL, responsable TINYINT(1) NOT NULL, INDEX IDX_8F02BF9D7A45358C (groupe_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        }

        if ($schema->hasTable('user_group') && !$schema->getTable('user_group')->hasForeignKey('FK_8F02BF9D7A45358C')) {
            $this->addSql('ALTER TABLE user_group ADD CONSTRAINT FK_8F02BF9D7A45358C FOREIGN KEY (groupe_id) REFERENCES `group` (id)');
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('user_group') && $schema->getTable('user_group')->hasForeignKey('FK_8F02BF9D7A45358C')) {
            $this->addSql('ALTER TABLE user_group DROP FOREIGN KEY FK_8F02BF9D7A45358C');
        }

        if ($schema->hasTable('user_group')) {
            $this->addSql('DROP TABLE user_group');
        }

        if ($schema->hasTable('group')) {
            $this->addSql('DROP TABLE `group`');
        }
    }
}
