<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250727123722 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add provider_user_id to Provider table and change access_token and refresh_token to LONGTEXT';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE provider ADD provider_user_id VARCHAR(255) DEFAULT NULL, CHANGE access_token access_token LONGTEXT DEFAULT NULL, CHANGE refresh_token refresh_token LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE provider DROP provider_user_id, CHANGE access_token access_token TEXT DEFAULT NULL, CHANGE refresh_token refresh_token TEXT DEFAULT NULL');
    }
}
