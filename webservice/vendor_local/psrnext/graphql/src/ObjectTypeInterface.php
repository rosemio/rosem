<?php

namespace Psrnext\GraphQL;

interface ObjectTypeInterface extends NodeInterface
{
    public function getName(): string;

    public function getFields(TypeRegistryInterface $typeRegistry): array;

    public function addFields(\Closure $factory): void;
}
