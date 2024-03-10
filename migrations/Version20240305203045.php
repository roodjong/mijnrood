<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240305203045 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Socialisten already has this column, so we're using IF NOT EXIST, and alter it again after update to set the default value
        $this->addSql('ALTER TABLE admin_member ADD COLUMN IF NOT EXISTS middle_name VARCHAR(50) DEFAULT \'\' NOT NULL');
        $this->addSql('ALTER TABLE admin_membership_application ADD COLUMN IF NOT EXISTS middle_name VARCHAR(50) DEFAULT \'\' NOT NULL');
        $this->addSql('UPDATE admin_member SET middle_name = \'\' WHERE middle_name IS NULL');
        $this->addSql('UPDATE admin_membership_application SET middle_name = \'\' WHERE middle_name IS NULL');
        $this->addSql('ALTER TABLE admin_member CHANGE middle_name middle_name VARCHAR(50) DEFAULT \'\' NOT NULL');
        $this->addSql('ALTER TABLE admin_membership_application CHANGE middle_name middle_name VARCHAR(50) DEFAULT \'\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE admin_member DROP middle_name');
        $this->addSql('ALTER TABLE admin_membership_application DROP middle_name');
    }
}
