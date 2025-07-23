<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250723180437 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update provider access token type and user email and first name nullable in the User and Provider tables';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE session CHANGE host_id host_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE user CHANGE first_name first_name VARCHAR(255) DEFAULT NULL, CHANGE email email VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE provider CHANGE access_token access_token TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE provider CHANGE refresh_token refresh_token TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE session CHANGE host_id host_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE user CHANGE first_name first_name VARCHAR(255) NOT NULL, CHANGE email email VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE provider CHANGE access_token access_token VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE provider CHANGE refresh_token refresh_token VARCHAR(255) DEFAULT NULL');
    }
}
