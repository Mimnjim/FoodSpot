<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261112182000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Normalize SQLite text columns used for group session state.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE group_session RENAME TO group_session_legacy');
        $this->addSql('CREATE TABLE group_session (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, code VARCHAR(12) NOT NULL, name VARCHAR(120) NOT NULL, city VARCHAR(120) NOT NULL, status VARCHAR(20) NOT NULL, expires_at DATETIME NOT NULL, participants CLOB NOT NULL, preferences CLOB NOT NULL, votes CLOB NOT NULL, result CLOB DEFAULT NULL)');
        $this->addSql('INSERT INTO group_session (id, code, name, city, status, expires_at, participants, preferences, votes, result) SELECT id, code, name, city, status, expires_at, participants, preferences, votes, result FROM group_session_legacy');
        $this->addSql('DROP TABLE group_session_legacy');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_564481D877153098 ON group_session (code)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_564481D877153098');
    }
}
