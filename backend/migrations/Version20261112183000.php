<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261112183000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store the participant authorized to decide a group session.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE group_session ADD creator_participant_id VARCHAR(24) NOT NULL DEFAULT ''");
        $this->addSql("UPDATE group_session SET creator_participant_id = COALESCE(json_extract(participants, '$[0].id'), 'legacy') WHERE creator_participant_id = ''");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE group_session DROP COLUMN creator_participant_id');
    }
}
