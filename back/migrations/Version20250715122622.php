<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250715122622 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create session table with host, name, code, max_participants, and ended_at fields,
            and create session_participant table with session_id, pseudo, left_at fields'
        ;
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("CREATE TABLE session (id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)', host_id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)', name VARCHAR(255) DEFAULT NULL, code VARCHAR(8) DEFAULT NULL, max_participants INT DEFAULT NULL, ended_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', created_by VARCHAR(255) DEFAULT NULL, updated_by VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_D044D5D477153098 (code), INDEX IDX_D044D5D41FB8D185 (host_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE session ADD CONSTRAINT FK_D044D5D41FB8D185 FOREIGN KEY (host_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE subscription CHANGE status status VARCHAR(255) DEFAULT NULL');

        $this->addSql('CREATE TABLE session_participant (
                id BINARY(16) NOT NULL COMMENT "(DC2Type:uuid)",
                session_id BINARY(16) NOT NULL COMMENT "(DC2Type:uuid)",
                pseudo VARCHAR(50) DEFAULT NULL,
                left_at DATETIME DEFAULT NULL COMMENT "(DC2Type:datetime_immutable)",
                created_by VARCHAR(255) DEFAULT NULL,
                updated_by VARCHAR(255) DEFAULT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX IDX_2BC67566613FECDF (session_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ');
        $this->addSql('ALTER TABLE session_participant ADD CONSTRAINT FK_2BC67566613FECDF FOREIGN KEY (session_id) REFERENCES session (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE session DROP FOREIGN KEY FK_D044D5D41FB8D185');
        $this->addSql('DROP TABLE session');
        $this->addSql("ALTER TABLE subscription CHANGE status status VARCHAR(255) DEFAULT 'active'");
        $this->addSql('ALTER TABLE session_participant DROP FOREIGN KEY FK_2BC67566613FECDF');
        $this->addSql('DROP TABLE session_participant');
    }
}
