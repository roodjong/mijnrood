<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211204213452 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial migration';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE admin_contribution_payment (id INT AUTO_INCREMENT NOT NULL, member_id INT DEFAULT NULL, amount_in_cents INT NOT NULL, payment_time DATETIME NOT NULL, status SMALLINT NOT NULL, mollie_payment_id VARCHAR(255) DEFAULT NULL, period_year SMALLINT UNSIGNED NOT NULL, period_month_start SMALLINT UNSIGNED NOT NULL, period_month_end SMALLINT UNSIGNED NOT NULL, INDEX IDX_D42E5D4C7597D3FE (member_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE admin_division (id INT AUTO_INCREMENT NOT NULL, contact_id INT DEFAULT NULL, email_id INT DEFAULT NULL, name VARCHAR(50) NOT NULL, phone VARCHAR(50) DEFAULT NULL, city VARCHAR(100) DEFAULT NULL, address VARCHAR(100) DEFAULT NULL, post_code VARCHAR(100) DEFAULT NULL, facebook VARCHAR(200) DEFAULT NULL, instagram VARCHAR(200) DEFAULT NULL, twitter VARCHAR(200) DEFAULT NULL, can_be_selected_on_application TINYINT(1) DEFAULT \'1\' NOT NULL, INDEX IDX_84B74012E7A1254A (contact_id), INDEX IDX_84B74012A832C1C9 (email_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE admin_document (id INT AUTO_INCREMENT NOT NULL, folder_id INT DEFAULT NULL, member_uploaded_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, size_in_bytes INT NOT NULL, upload_file_name VARCHAR(255) NOT NULL, date_uploaded DATETIME NOT NULL, INDEX IDX_4CC98D70162CB942 (folder_id), INDEX IDX_4CC98D70BA2890F6 (member_uploaded_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE admin_document_folder (id INT AUTO_INCREMENT NOT NULL, parent_id INT DEFAULT NULL, member_created_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, INDEX IDX_6E10544C727ACA70 (parent_id), INDEX IDX_6E10544CB5AE00AD (member_created_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE admin_email (id INT AUTO_INCREMENT NOT NULL, domain_id INT NOT NULL, manager_id INT DEFAULT NULL, user VARCHAR(100) NOT NULL, INDEX IDX_47B8878E115F0EE5 (domain_id), INDEX IDX_47B8878E783E3463 (manager_id), UNIQUE INDEX email (user, domain_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE admin_email_domain (id INT AUTO_INCREMENT NOT NULL, domain VARCHAR(100) NOT NULL, UNIQUE INDEX domain (domain), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE admin_event (id INT UNSIGNED AUTO_INCREMENT NOT NULL, division_id INT DEFAULT NULL, name VARCHAR(150) NOT NULL, description VARCHAR(2000) NOT NULL, time_start DATETIME NOT NULL, time_end DATETIME NOT NULL, INDEX IDX_9B84F15D41859289 (division_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE admin_member (id INT AUTO_INCREMENT NOT NULL, division_id INT DEFAULT NULL, first_name VARCHAR(50) NOT NULL, last_name VARCHAR(100) NOT NULL, email VARCHAR(200) DEFAULT NULL, phone VARCHAR(20) NOT NULL, iban VARCHAR(34) DEFAULT NULL, address VARCHAR(100) NOT NULL, city VARCHAR(100) NOT NULL, post_code VARCHAR(14) NOT NULL, country VARCHAR(2) NOT NULL, date_of_birth DATE DEFAULT NULL, registration_time DATE DEFAULT NULL, mollie_customer_id VARCHAR(255) DEFAULT NULL, mollie_subscription_id VARCHAR(255) DEFAULT NULL, create_subscription_after_payment TINYINT(1) DEFAULT \'0\' NOT NULL, contribution_period INT DEFAULT 2 NOT NULL, contribution_per_period_in_cents INT DEFAULT 500 NOT NULL, roles LONGTEXT DEFAULT \'[]\' NOT NULL COMMENT \'(DC2Type:json)\', password_hash VARCHAR(100) DEFAULT NULL, new_password_token_generated_time DATETIME DEFAULT NULL, new_password_token VARCHAR(255) DEFAULT NULL, accept_use_personal_information TINYINT(1) DEFAULT \'0\' NOT NULL, INDEX IDX_2D2CCB8141859289 (division_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE admin_member_revision (id INT AUTO_INCREMENT NOT NULL, member_id INT NOT NULL, own TINYINT(1) NOT NULL, revision_time DATETIME NOT NULL, first_name VARCHAR(50) NOT NULL, last_name VARCHAR(100) NOT NULL, email VARCHAR(200) NOT NULL, phone VARCHAR(20) NOT NULL, iban VARCHAR(34) DEFAULT NULL, address VARCHAR(100) NOT NULL, city VARCHAR(100) NOT NULL, post_code VARCHAR(14) NOT NULL, country VARCHAR(2) NOT NULL, date_of_birth DATE DEFAULT NULL, INDEX IDX_A66E5AC17597D3FE (member_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE admin_membership_application (id INT AUTO_INCREMENT NOT NULL, preferred_division_id INT DEFAULT NULL, first_name VARCHAR(50) NOT NULL, last_name VARCHAR(100) NOT NULL, email VARCHAR(200) NOT NULL, phone VARCHAR(20) NOT NULL, iban VARCHAR(34) DEFAULT NULL, address VARCHAR(100) NOT NULL, city VARCHAR(100) NOT NULL, post_code VARCHAR(14) NOT NULL, country VARCHAR(2) NOT NULL, date_of_birth DATE DEFAULT NULL, registration_time DATE DEFAULT NULL, contribution_period INT DEFAULT 0 NOT NULL, contribution_per_period_in_cents INT DEFAULT 0 NOT NULL, INDEX IDX_3530F3443FA00F8 (preferred_division_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE admin_support_member (id INT AUTO_INCREMENT NOT NULL, first_name VARCHAR(50) NOT NULL, last_name VARCHAR(100) NOT NULL, email VARCHAR(200) DEFAULT NULL, phone VARCHAR(20) NOT NULL, iban VARCHAR(34) DEFAULT NULL, address VARCHAR(100) NOT NULL, city VARCHAR(100) NOT NULL, post_code VARCHAR(14) NOT NULL, country VARCHAR(2) NOT NULL, date_of_birth DATE DEFAULT NULL, registration_time DATE DEFAULT NULL, mollie_customer_id VARCHAR(255) DEFAULT NULL, mollie_subscription_id VARCHAR(255) DEFAULT NULL, contribution_period INT DEFAULT 2 NOT NULL, contribution_per_period_in_cents INT DEFAULT 500 NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE admin_support_membership_application (id INT AUTO_INCREMENT NOT NULL, first_name VARCHAR(50) NOT NULL, last_name VARCHAR(100) NOT NULL, email VARCHAR(200) DEFAULT NULL, phone VARCHAR(20) NOT NULL, iban VARCHAR(34) DEFAULT NULL, address VARCHAR(100) NOT NULL, city VARCHAR(100) NOT NULL, post_code VARCHAR(14) NOT NULL, country VARCHAR(2) NOT NULL, date_of_birth DATE DEFAULT NULL, registration_time DATE DEFAULT NULL, mollie_customer_id VARCHAR(255) DEFAULT NULL, mollie_subscription_id VARCHAR(255) DEFAULT NULL, contribution_period INT DEFAULT 0 NOT NULL, contribution_per_period_in_cents INT DEFAULT 500 NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE admin_contribution_payment ADD CONSTRAINT FK_D42E5D4C7597D3FE FOREIGN KEY (member_id) REFERENCES admin_member (id)');
        $this->addSql('ALTER TABLE admin_division ADD CONSTRAINT FK_84B74012E7A1254A FOREIGN KEY (contact_id) REFERENCES admin_member (id)');
        $this->addSql('ALTER TABLE admin_division ADD CONSTRAINT FK_84B74012A832C1C9 FOREIGN KEY (email_id) REFERENCES admin_email (id)');
        $this->addSql('ALTER TABLE admin_document ADD CONSTRAINT FK_4CC98D70162CB942 FOREIGN KEY (folder_id) REFERENCES admin_document_folder (id)');
        $this->addSql('ALTER TABLE admin_document ADD CONSTRAINT FK_4CC98D70BA2890F6 FOREIGN KEY (member_uploaded_id) REFERENCES admin_member (id)');
        $this->addSql('ALTER TABLE admin_document_folder ADD CONSTRAINT FK_6E10544C727ACA70 FOREIGN KEY (parent_id) REFERENCES admin_document_folder (id)');
        $this->addSql('ALTER TABLE admin_document_folder ADD CONSTRAINT FK_6E10544CB5AE00AD FOREIGN KEY (member_created_id) REFERENCES admin_member (id)');
        $this->addSql('ALTER TABLE admin_email ADD CONSTRAINT FK_47B8878E115F0EE5 FOREIGN KEY (domain_id) REFERENCES admin_email_domain (id)');
        $this->addSql('ALTER TABLE admin_email ADD CONSTRAINT FK_47B8878E783E3463 FOREIGN KEY (manager_id) REFERENCES admin_member (id)');
        $this->addSql('ALTER TABLE admin_event ADD CONSTRAINT FK_9B84F15D41859289 FOREIGN KEY (division_id) REFERENCES admin_division (id)');
        $this->addSql('ALTER TABLE admin_member ADD CONSTRAINT FK_2D2CCB8141859289 FOREIGN KEY (division_id) REFERENCES admin_division (id)');
        $this->addSql('ALTER TABLE admin_member_revision ADD CONSTRAINT FK_A66E5AC17597D3FE FOREIGN KEY (member_id) REFERENCES admin_member (id)');
        $this->addSql('ALTER TABLE admin_membership_application ADD CONSTRAINT FK_3530F3443FA00F8 FOREIGN KEY (preferred_division_id) REFERENCES admin_division (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE admin_event DROP FOREIGN KEY FK_9B84F15D41859289');
        $this->addSql('ALTER TABLE admin_member DROP FOREIGN KEY FK_2D2CCB8141859289');
        $this->addSql('ALTER TABLE admin_membership_application DROP FOREIGN KEY FK_3530F3443FA00F8');
        $this->addSql('ALTER TABLE admin_document DROP FOREIGN KEY FK_4CC98D70162CB942');
        $this->addSql('ALTER TABLE admin_document_folder DROP FOREIGN KEY FK_6E10544C727ACA70');
        $this->addSql('ALTER TABLE admin_division DROP FOREIGN KEY FK_84B74012A832C1C9');
        $this->addSql('ALTER TABLE admin_email DROP FOREIGN KEY FK_47B8878E115F0EE5');
        $this->addSql('ALTER TABLE admin_contribution_payment DROP FOREIGN KEY FK_D42E5D4C7597D3FE');
        $this->addSql('ALTER TABLE admin_division DROP FOREIGN KEY FK_84B74012E7A1254A');
        $this->addSql('ALTER TABLE admin_document DROP FOREIGN KEY FK_4CC98D70BA2890F6');
        $this->addSql('ALTER TABLE admin_document_folder DROP FOREIGN KEY FK_6E10544CB5AE00AD');
        $this->addSql('ALTER TABLE admin_email DROP FOREIGN KEY FK_47B8878E783E3463');
        $this->addSql('ALTER TABLE admin_member_revision DROP FOREIGN KEY FK_A66E5AC17597D3FE');
        $this->addSql('DROP TABLE admin_contribution_payment');
        $this->addSql('DROP TABLE admin_division');
        $this->addSql('DROP TABLE admin_document');
        $this->addSql('DROP TABLE admin_document_folder');
        $this->addSql('DROP TABLE admin_email');
        $this->addSql('DROP TABLE admin_email_domain');
        $this->addSql('DROP TABLE admin_event');
        $this->addSql('DROP TABLE admin_member');
        $this->addSql('DROP TABLE admin_member_revision');
        $this->addSql('DROP TABLE admin_membership_application');
        $this->addSql('DROP TABLE admin_support_member');
        $this->addSql('DROP TABLE admin_support_membership_application');
    }
}
