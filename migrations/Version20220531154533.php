<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220531154533 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE division_member (division_id INT NOT NULL, member_id INT NOT NULL, INDEX IDX_66CF3FA885C4074C (division_id), INDEX IDX_66CF3FA8A76ED395 (member_id), PRIMARY KEY(division_id, member_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE division_member ADD CONSTRAINT FK_66CF3FA885C4074C FOREIGN KEY (division_id) REFERENCES admin_division (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE division_member ADD CONSTRAINT FK_66CF3FA8A76ED395 FOREIGN KEY (member_id) REFERENCES admin_member (id) ON DELETE CASCADE');

        // migrate the existing data
        $this->addSql('INSERT INTO division_member (division_id, member_id) SELECT id as division_id, contact_id as member_id FROM admin_division WHERE contact_id IS NOT NULL');

        // drop the previous data
        $this->addSql('ALTER TABLE admin_division DROP FOREIGN KEY FK_84B74012E7A1254A');
        $this->addSql('DROP INDEX IDX_84B74012E7A1254A ON admin_division');
        $this->addSql('ALTER TABLE admin_division DROP contact_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE admin_division ADD contact_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE admin_division ADD CONSTRAINT FK_84B74012E7A1254A FOREIGN KEY (contact_id) REFERENCES admin_member (id)');
        $this->addSql('CREATE INDEX IDX_84B74012E7A1254A ON admin_division (contact_id)');
        $this->addSql('DROP TABLE division_member');
    }
}
