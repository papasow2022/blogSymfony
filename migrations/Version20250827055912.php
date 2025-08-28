<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250827055912 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE comment ADD is_approved TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE post ADD views_count INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE user ADD is_blocked TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE comment DROP is_approved');
        $this->addSql('ALTER TABLE post DROP views_count');
        $this->addSql('ALTER TABLE user DROP is_blocked');
    }
}
