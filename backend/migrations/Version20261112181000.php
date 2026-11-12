<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261112181000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Align the group session code index with Doctrine metadata.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_GROUP_SESSION_CODE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_564481D877153098 ON group_session (code)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_564481D877153098');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_GROUP_SESSION_CODE ON group_session (code)');
    }
}
