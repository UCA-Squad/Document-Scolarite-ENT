<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260313143901 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('imported_data')) {
            return;
        }

        $table = $schema->getTable('imported_data');
        $semestreDefault = $table->hasColumn('semestre') ? $table->getColumn('semestre')->getDefault() : null;
        $sessionDefault = $table->hasColumn('session') ? $table->getColumn('session')->getDefault() : null;
        $libelleFormDefault = $table->hasColumn('libelle_form') ? $table->getColumn('libelle_form')->getDefault() : null;

        if ($semestreDefault === 'NULL' || $sessionDefault === 'NULL' || $libelleFormDefault === 'NULL') {
            $this->addSql('ALTER TABLE imported_data CHANGE semestre semestre VARCHAR(3) DEFAULT NULL, CHANGE session session VARCHAR(1) DEFAULT NULL, CHANGE libelle_form libelle_form VARCHAR(100) DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('imported_data')) {
            return;
        }

        $table = $schema->getTable('imported_data');
        $semestreDefault = $table->hasColumn('semestre') ? $table->getColumn('semestre')->getDefault() : null;
        $sessionDefault = $table->hasColumn('session') ? $table->getColumn('session')->getDefault() : null;
        $libelleFormDefault = $table->hasColumn('libelle_form') ? $table->getColumn('libelle_form')->getDefault() : null;

        if ($semestreDefault !== 'NULL' || $sessionDefault !== 'NULL' || $libelleFormDefault !== 'NULL') {
            $this->addSql('ALTER TABLE imported_data CHANGE semestre semestre VARCHAR(3) DEFAULT \'NULL\', CHANGE session session VARCHAR(1) DEFAULT \'NULL\', CHANGE libelle_form libelle_form VARCHAR(100) DEFAULT \'NULL\'');
        }
    }
}
