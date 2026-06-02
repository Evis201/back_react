<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260602130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add school, domain, study_year, cv_url to student';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE student ADD school VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE student ADD domain VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE student ADD study_year SMALLINT DEFAULT NULL');
        $this->addSql('ALTER TABLE student ADD cv_url VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE student DROP COLUMN school');
        $this->addSql('ALTER TABLE student DROP COLUMN domain');
        $this->addSql('ALTER TABLE student DROP COLUMN study_year');
        $this->addSql('ALTER TABLE student DROP COLUMN cv_url');
    }
}
