<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261112184000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove the temporary migration default from the creator participant column.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TEMPORARY TABLE group_session_creator_fix AS SELECT id, code, name, city, status, expires_at, participants, preferences, votes, result, creator_participant_id FROM group_session');
        $this->addSql('DROP TABLE group_session');
        $this->addSql('CREATE TABLE group_session (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, code VARCHAR(12) NOT NULL, name VARCHAR(120) NOT NULL, city VARCHAR(120) NOT NULL, status VARCHAR(20) NOT NULL, expires_at DATETIME NOT NULL, participants CLOB NOT NULL, preferences CLOB NOT NULL, votes CLOB NOT NULL, result CLOB DEFAULT NULL, creator_participant_id VARCHAR(24) NOT NULL)');
        $this->addSql('INSERT INTO group_session (id, code, name, city, status, expires_at, participants, preferences, votes, result, creator_participant_id) SELECT id, code, name, city, status, expires_at, participants, preferences, votes, result, creator_participant_id FROM group_session_creator_fix');
        $this->addSql('DROP TABLE group_session_creator_fix');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_564481D877153098 ON group_session (code)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_564481D877153098');
    }
}
