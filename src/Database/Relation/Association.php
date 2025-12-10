<?php

namespace Minz\Database\Relation;

abstract class Association
{
    public function __construct(
        public string $column,
    ) {
    }

    abstract public function load(\Minz\Database\Relation $relation): mixed;
}
