<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260523134202 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE accountant_invitations (id INT AUTO_INCREMENT NOT NULL, token VARCHAR(64) NOT NULL, status VARCHAR(20) DEFAULT \'pending\' NOT NULL, created_at DATETIME NOT NULL, expires_at DATETIME DEFAULT NULL, responded_at DATETIME DEFAULT NULL, accountant_id INT NOT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_99DCACED5F37A13B (token), INDEX IDX_99DCACED9582AA74 (accountant_id), INDEX IDX_99DCACEDA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE accountants (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, firm VARCHAR(255) DEFAULT NULL, registration_number VARCHAR(50) DEFAULT NULL, stamp_path VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_6F587580E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE ai_conversations (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, user_id INT NOT NULL, INDEX IDX_F36727D7A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE ai_messages (id INT AUTO_INCREMENT NOT NULL, role VARCHAR(20) NOT NULL, content LONGTEXT NOT NULL, input_tokens INT DEFAULT NULL, output_tokens INT DEFAULT NULL, created_at DATETIME NOT NULL, conversation_id INT NOT NULL, INDEX IDX_C4E498F69AC0396 (conversation_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE balance_sheets (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(20) NOT NULL, period VARCHAR(10) NOT NULL, period_start DATE NOT NULL, period_end DATE NOT NULL, financial_data JSON DEFAULT NULL, ai_analysis LONGTEXT DEFAULT NULL, accountant_annotations JSON DEFAULT NULL, accountant_comment LONGTEXT DEFAULT NULL, stamp_path VARCHAR(255) DEFAULT NULL, validated_at DATETIME DEFAULT NULL, pdf_path VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, accountant_id INT DEFAULT NULL, INDEX IDX_D07636A7A76ED395 (user_id), INDEX IDX_D07636A79582AA74 (accountant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE clients (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, siret VARCHAR(14) DEFAULT NULL, email VARCHAR(180) DEFAULT NULL, phone VARCHAR(20) DEFAULT NULL, address VARCHAR(255) DEFAULT NULL, postal_code VARCHAR(10) DEFAULT NULL, city VARCHAR(100) DEFAULT NULL, country VARCHAR(2) DEFAULT \'FR\' NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_C82E74A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE expense_categories (id INT AUTO_INCREMENT NOT NULL, slug VARCHAR(50) NOT NULL, label VARCHAR(100) NOT NULL, deductible TINYINT DEFAULT 1 NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE expenses (id INT AUTO_INCREMENT NOT NULL, vendor VARCHAR(255) NOT NULL, date DATE NOT NULL, amount_ttc NUMERIC(10, 2) NOT NULL, amount_ht NUMERIC(10, 2) DEFAULT 0 NOT NULL, tva NUMERIC(10, 2) DEFAULT 0 NOT NULL, tva_rate NUMERIC(5, 2) DEFAULT 0 NOT NULL, payment_method VARCHAR(50) DEFAULT NULL, deductible TINYINT DEFAULT 1 NOT NULL, notes LONGTEXT DEFAULT NULL, receipt_path VARCHAR(255) DEFAULT NULL, ocr_data JSON DEFAULT NULL, ocr_confidence NUMERIC(3, 2) DEFAULT NULL, ocr_verified TINYINT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, category_id INT DEFAULT NULL, INDEX IDX_2496F35BA76ED395 (user_id), INDEX IDX_2496F35B12469DE2 (category_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE invoice_lines (id INT AUTO_INCREMENT NOT NULL, description VARCHAR(255) NOT NULL, quantity NUMERIC(10, 2) NOT NULL, unit_price NUMERIC(10, 2) NOT NULL, tva_rate NUMERIC(5, 2) DEFAULT 0 NOT NULL, total_ht NUMERIC(10, 2) DEFAULT 0 NOT NULL, total_tva NUMERIC(10, 2) DEFAULT 0 NOT NULL, position INT DEFAULT 0 NOT NULL, invoice_id INT NOT NULL, INDEX IDX_72DBDC232989F1FD (invoice_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE invoices (id INT AUTO_INCREMENT NOT NULL, number VARCHAR(30) NOT NULL, status VARCHAR(20) DEFAULT \'draft\' NOT NULL, issued_at DATE NOT NULL, due_at DATE DEFAULT NULL, paid_at DATE DEFAULT NULL, total_ht NUMERIC(10, 2) DEFAULT 0 NOT NULL, total_tva NUMERIC(10, 2) DEFAULT 0 NOT NULL, total_ttc NUMERIC(10, 2) DEFAULT 0 NOT NULL, currency VARCHAR(3) DEFAULT \'EUR\' NOT NULL, notes LONGTEXT DEFAULT NULL, pdf_path VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, client_id INT NOT NULL, UNIQUE INDEX UNIQ_6A2F2F9596901F54 (number), INDEX IDX_6A2F2F95A76ED395 (user_id), INDEX IDX_6A2F2F9519EB6921 (client_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE urssaf_declarations (id INT AUTO_INCREMENT NOT NULL, periodicity VARCHAR(10) NOT NULL, period_label VARCHAR(10) NOT NULL, period_start DATE NOT NULL, period_end DATE NOT NULL, revenue NUMERIC(12, 2) NOT NULL, cotisation_rate NUMERIC(5, 4) NOT NULL, cotisation_amount NUMERIC(12, 2) NOT NULL, declared TINYINT DEFAULT 0 NOT NULL, declared_at DATE DEFAULT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_259B39C6A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, siret VARCHAR(14) DEFAULT NULL, phone VARCHAR(20) DEFAULT NULL, plan VARCHAR(20) DEFAULT \'free\' NOT NULL, stripe_customer_id VARCHAR(255) DEFAULT NULL, stripe_subscription_id VARCHAR(255) DEFAULT NULL, subscription_ends_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE accountant_invitations ADD CONSTRAINT FK_99DCACED9582AA74 FOREIGN KEY (accountant_id) REFERENCES accountants (id)');
        $this->addSql('ALTER TABLE accountant_invitations ADD CONSTRAINT FK_99DCACEDA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE ai_conversations ADD CONSTRAINT FK_F36727D7A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ai_messages ADD CONSTRAINT FK_C4E498F69AC0396 FOREIGN KEY (conversation_id) REFERENCES ai_conversations (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE balance_sheets ADD CONSTRAINT FK_D07636A7A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE balance_sheets ADD CONSTRAINT FK_D07636A79582AA74 FOREIGN KEY (accountant_id) REFERENCES accountants (id)');
        $this->addSql('ALTER TABLE clients ADD CONSTRAINT FK_C82E74A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE expenses ADD CONSTRAINT FK_2496F35BA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE expenses ADD CONSTRAINT FK_2496F35B12469DE2 FOREIGN KEY (category_id) REFERENCES expense_categories (id)');
        $this->addSql('ALTER TABLE invoice_lines ADD CONSTRAINT FK_72DBDC232989F1FD FOREIGN KEY (invoice_id) REFERENCES invoices (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE invoices ADD CONSTRAINT FK_6A2F2F95A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE invoices ADD CONSTRAINT FK_6A2F2F9519EB6921 FOREIGN KEY (client_id) REFERENCES clients (id)');
        $this->addSql('ALTER TABLE urssaf_declarations ADD CONSTRAINT FK_259B39C6A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE accountant_invitations DROP FOREIGN KEY FK_99DCACED9582AA74');
        $this->addSql('ALTER TABLE accountant_invitations DROP FOREIGN KEY FK_99DCACEDA76ED395');
        $this->addSql('ALTER TABLE ai_conversations DROP FOREIGN KEY FK_F36727D7A76ED395');
        $this->addSql('ALTER TABLE ai_messages DROP FOREIGN KEY FK_C4E498F69AC0396');
        $this->addSql('ALTER TABLE balance_sheets DROP FOREIGN KEY FK_D07636A7A76ED395');
        $this->addSql('ALTER TABLE balance_sheets DROP FOREIGN KEY FK_D07636A79582AA74');
        $this->addSql('ALTER TABLE clients DROP FOREIGN KEY FK_C82E74A76ED395');
        $this->addSql('ALTER TABLE expenses DROP FOREIGN KEY FK_2496F35BA76ED395');
        $this->addSql('ALTER TABLE expenses DROP FOREIGN KEY FK_2496F35B12469DE2');
        $this->addSql('ALTER TABLE invoice_lines DROP FOREIGN KEY FK_72DBDC232989F1FD');
        $this->addSql('ALTER TABLE invoices DROP FOREIGN KEY FK_6A2F2F95A76ED395');
        $this->addSql('ALTER TABLE invoices DROP FOREIGN KEY FK_6A2F2F9519EB6921');
        $this->addSql('ALTER TABLE urssaf_declarations DROP FOREIGN KEY FK_259B39C6A76ED395');
        $this->addSql('DROP TABLE accountant_invitations');
        $this->addSql('DROP TABLE accountants');
        $this->addSql('DROP TABLE ai_conversations');
        $this->addSql('DROP TABLE ai_messages');
        $this->addSql('DROP TABLE balance_sheets');
        $this->addSql('DROP TABLE clients');
        $this->addSql('DROP TABLE expense_categories');
        $this->addSql('DROP TABLE expenses');
        $this->addSql('DROP TABLE invoice_lines');
        $this->addSql('DROP TABLE invoices');
        $this->addSql('DROP TABLE urssaf_declarations');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
