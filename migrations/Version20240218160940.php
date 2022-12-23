<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240218160940 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add membership status';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE admin_membershipstatus (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(150) NOT NULL, allowed_access TINYINT(1) DEFAULT \'0\' NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE admin_member ADD current_membership_status_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE admin_member ADD CONSTRAINT FK_2D2CCB81E1F48A9F FOREIGN KEY (current_membership_status_id) REFERENCES admin_membershipstatus (id)');
        $this->addSql('CREATE INDEX IDX_2D2CCB81E1F48A9F ON admin_member (current_membership_status_id)');
        $this->addSql("INSERT INTO admin_membershipstatus VALUES (1, 'Lid', TRUE)");
        $this->addSql("UPDATE admin_member SET current_membership_status_id = 1");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE admin_member DROP FOREIGN KEY FK_2D2CCB81E1F48A9F');
        $this->addSql('DROP TABLE admin_membershipstatus');
        $this->addSql('DROP INDEX IDX_2D2CCB81E1F48A9F ON admin_member');
        $this->addSql('ALTER TABLE admin_member DROP current_membership_status_id');
    }
}
