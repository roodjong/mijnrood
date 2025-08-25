<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250525151750 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE admin_event_attendant (event_id INT UNSIGNED NOT NULL, member_id INT NOT NULL, reserved DATETIME DEFAULT NULL, checked_in TINYINT(1) DEFAULT 0 NOT NULL, INDEX IDX_8155D4B71F7E88B (event_id), INDEX IDX_8155D4B7597D3FE (member_id), PRIMARY KEY(event_id, member_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE admin_event_attendant ADD CONSTRAINT FK_8155D4B71F7E88B FOREIGN KEY (event_id) REFERENCES admin_event (id)');
        $this->addSql('ALTER TABLE admin_event_attendant ADD CONSTRAINT FK_8155D4B7597D3FE FOREIGN KEY (member_id) REFERENCES admin_member (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE admin_event_attendant DROP FOREIGN KEY FK_8155D4B71F7E88B');
        $this->addSql('ALTER TABLE admin_event_attendant DROP FOREIGN KEY FK_8155D4B7597D3FE');
        $this->addSql('DROP TABLE admin_event_attendant');
    }
}
