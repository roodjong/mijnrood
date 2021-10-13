<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210926144456 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE admin_support_member (id INT AUTO_INCREMENT NOT NULL, first_name VARCHAR(50) NOT NULL, last_name VARCHAR(100) NOT NULL, email VARCHAR(200) DEFAULT NULL, phone VARCHAR(20) NOT NULL, iban VARCHAR(34) DEFAULT NULL, address VARCHAR(100) NOT NULL, city VARCHAR(100) NOT NULL, post_code VARCHAR(14) NOT NULL, country VARCHAR(2) NOT NULL, date_of_birth DATE DEFAULT NULL, registration_time DATE DEFAULT NULL, mollie_customer_id VARCHAR(255) DEFAULT NULL, mollie_subscription_id VARCHAR(255) DEFAULT NULL, contribution_period INT DEFAULT 2 NOT NULL, contribution_per_period_in_cents INT DEFAULT 500 NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE admin_support_membership_application (id INT AUTO_INCREMENT NOT NULL, first_name VARCHAR(50) NOT NULL, last_name VARCHAR(100) NOT NULL, email VARCHAR(200) DEFAULT NULL, phone VARCHAR(20) NOT NULL, iban VARCHAR(34) DEFAULT NULL, address VARCHAR(100) NOT NULL, city VARCHAR(100) NOT NULL, post_code VARCHAR(14) NOT NULL, country VARCHAR(2) NOT NULL, date_of_birth DATE DEFAULT NULL, registration_time DATE DEFAULT NULL, mollie_customer_id VARCHAR(255) DEFAULT NULL, mollie_subscription_id VARCHAR(255) DEFAULT NULL, contribution_period INT DEFAULT 2 NOT NULL, contribution_per_period_in_cents INT DEFAULT 500 NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE admin_support_member');
        $this->addSql('DROP TABLE admin_support_membership_application');
    }
}
