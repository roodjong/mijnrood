<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240224201202 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE admin_member_revision ADD current_membership_status_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE admin_member_revision ADD CONSTRAINT FK_A66E5AC1E1F48A9F FOREIGN KEY (current_membership_status_id) REFERENCES admin_membershipstatus (id)');
        $this->addSql('CREATE INDEX IDX_A66E5AC1E1F48A9F ON admin_member_revision (current_membership_status_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE admin_member_revision DROP FOREIGN KEY FK_A66E5AC1E1F48A9F');
        $this->addSql('DROP INDEX IDX_A66E5AC1E1F48A9F ON admin_member_revision');
        $this->addSql('ALTER TABLE admin_member_revision DROP current_membership_status_id');
    }
}
