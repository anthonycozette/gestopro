<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260524092555 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE expert_messages (id INT AUTO_INCREMENT NOT NULL, sender_type VARCHAR(10) NOT NULL, content LONGTEXT NOT NULL, created_at DATETIME NOT NULL, invitation_id INT NOT NULL, INDEX IDX_3F7EB3E8A35D7AF0 (invitation_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE expert_messages ADD CONSTRAINT FK_3F7EB3E8A35D7AF0 FOREIGN KEY (invitation_id) REFERENCES accountant_invitations (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE expert_messages DROP FOREIGN KEY FK_3F7EB3E8A35D7AF0');
        $this->addSql('DROP TABLE expert_messages');
    }
}
