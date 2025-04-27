<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250413112115 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create plan and subscription tables. Set plans for users.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE plan (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', name VARCHAR(255) NOT NULL, is_custom TINYINT(1) NOT NULL, created_by VARCHAR(255) DEFAULT NULL, updated_by VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, deleted_at DATETIME DEFAULT NULL, price_amount BIGINT DEFAULT NULL, price_currency VARCHAR(255) NOT NULL, stripe_price_id VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE subscription (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', user_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', plan_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', stripe_subscription_id VARCHAR(255) DEFAULT NULL, start_date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', end_date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_by VARCHAR(255) DEFAULT NULL, updated_by VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, deleted_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_A3C664D3A76ED395 (user_id), INDEX IDX_A3C664D3E899029B (plan_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D3A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D3E899029B FOREIGN KEY (plan_id) REFERENCES plan (id)');

        $this->addSql("
            INSERT INTO plan (id, name, is_custom, price_amount, price_currency, stripe_price_id, created_by, updated_by, created_at, updated_at)
            VALUES
                (UNHEX(REPLACE(UUID(), '-', '')), 'free', 0, 0, 'EUR', 'price_1RDO1MFR9LUheRIcMWMqLKmk', NULL, NULL, NOW(), NOW()),
                (UNHEX(REPLACE(UUID(), '-', '')), 'premium', 0, 399, 'EUR', 'price_1RIUF7FR9LUheRIcPKatNzhA', NULL, NULL, NOW(), NOW()),
                (UNHEX(REPLACE(UUID(), '-', '')), 'custom', 1, NULL, 'EUR', NULL, NULL, NULL, NOW(), NOW())
        ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D3A76ED395');
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D3E899029B');
        $this->addSql('DROP TABLE plan');
        $this->addSql('DROP TABLE subscription');
    }
}
