<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260527100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add invoice_number column to expenses table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE expenses ADD invoice_number VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE expenses DROP invoice_number');
    }
}
