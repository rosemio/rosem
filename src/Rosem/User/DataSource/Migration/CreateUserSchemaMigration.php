<?php

namespace Rosem\User\Database\Migration;

use Psrnext\Database\MigrationInterface;
use Psrnext\Database\SchemaInterface;

final class CreateUserSchemaMigration implements MigrationInterface
{
    public function up(SchemaInterface $schema): void
    {
        $schema->table('users')
            ->addColumn('id', 'integer', ['autoincrement' => true])
            ->create();
    }

    public function down(SchemaInterface $schema): void
    {
        $schema->table('users')->drop();
    }
}
