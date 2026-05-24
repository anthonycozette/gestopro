<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260524181401 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_6A2F2F9596901F54 ON invoices');
        $this->addSql('CREATE UNIQUE INDEX uniq_invoice_user_number ON invoices (user_id, number)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX uniq_invoice_user_number ON invoices');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6A2F2F9596901F54 ON invoices (number)');
    }
}
