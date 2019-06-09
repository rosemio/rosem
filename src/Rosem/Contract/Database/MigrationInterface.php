<?php

namespace Rosem\Contract\Database;

interface MigrationInterface
{
    public function up(SchemaInterface $schema): void;

    public function down(SchemaInterface $schema): void;
}
