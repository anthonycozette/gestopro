<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260525135257 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE invoices ADD signature_token VARCHAR(64) DEFAULT NULL, ADD signed_at DATETIME DEFAULT NULL, ADD signer_ip VARCHAR(45) DEFAULT NULL, ADD signature_data LONGTEXT DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6A2F2F95D7605360 ON invoices (signature_token)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_6A2F2F95D7605360 ON invoices');
        $this->addSql('ALTER TABLE invoices DROP signature_token, DROP signed_at, DROP signer_ip, DROP signature_data');
    }
}
