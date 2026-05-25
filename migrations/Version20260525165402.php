<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260525165402 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE invoices ADD converted_invoice_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE invoices ADD CONSTRAINT FK_6A2F2F959DC100AB FOREIGN KEY (converted_invoice_id) REFERENCES invoices (id) ON DELETE SET NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6A2F2F959DC100AB ON invoices (converted_invoice_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE invoices DROP FOREIGN KEY FK_6A2F2F959DC100AB');
        $this->addSql('DROP INDEX UNIQ_6A2F2F959DC100AB ON invoices');
        $this->addSql('ALTER TABLE invoices DROP converted_invoice_id');
    }
}
