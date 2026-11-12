<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261112180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add group discovery sessions and participant state.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE group_session (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, code VARCHAR(12) NOT NULL, name VARCHAR(120) NOT NULL, city VARCHAR(120) NOT NULL, status VARCHAR(20) NOT NULL, expires_at DATETIME NOT NULL, participants CLOB NOT NULL --(DC2Type:json)
, preferences CLOB NOT NULL --(DC2Type:json)
, votes CLOB NOT NULL --(DC2Type:json)
, result CLOB DEFAULT NULL --(DC2Type:json)
)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_GROUP_SESSION_CODE ON group_session (code)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE group_session');
    }
}
