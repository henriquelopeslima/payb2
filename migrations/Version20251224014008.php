<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251224014008 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE transfers (amount BIGINT NOT NULL, status VARCHAR(50) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id UUID NOT NULL, payer_id UUID NOT NULL, payee_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_802A3918C17AD9A9 ON transfers (payer_id)');
        $this->addSql('CREATE INDEX IDX_802A3918CB4B68F ON transfers (payee_id)');
        $this->addSql('CREATE TABLE users (full_name VARCHAR(255) NOT NULL, document VARCHAR(50) NOT NULL, email VARCHAR(180) NOT NULL, password_hash VARCHAR(255) NOT NULL, type VARCHAR(50) NOT NULL, id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9D8698A76 ON users (document)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
        $this->addSql('CREATE TABLE wallets (balance_amount BIGINT NOT NULL, id UUID NOT NULL, user_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_967AAA6CA76ED395 ON wallets (user_id)');
        $this->addSql('ALTER TABLE transfers ADD CONSTRAINT FK_802A3918C17AD9A9 FOREIGN KEY (payer_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE transfers ADD CONSTRAINT FK_802A3918CB4B68F FOREIGN KEY (payee_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE wallets ADD CONSTRAINT FK_967AAA6CA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE transfers DROP CONSTRAINT FK_802A3918C17AD9A9');
        $this->addSql('ALTER TABLE transfers DROP CONSTRAINT FK_802A3918CB4B68F');
        $this->addSql('ALTER TABLE wallets DROP CONSTRAINT FK_967AAA6CA76ED395');
        $this->addSql('DROP TABLE transfers');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE wallets');
    }
}
