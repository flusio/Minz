<?php

namespace Minz\Database;

class Relation
{
    public function __construct(
        private object $instance,
        private \ReflectionProperty $property,
        private Relation\Association $association,
    ) {
    }

    public function load(): mixed
    {
        return $this->association->load($this);
    }

    public function set(mixed $value): void
    {
        $this->setForeignKeyValue($value);
        $this->setForeignValue($value);
    }

    public function setForeignValue(mixed $value): void
    {
        $this->property->setValue($this->instance, $value);
    }

    public function setForeignKeyValue(mixed $value): void
    {
        $foreign_key = $this->foreignKey();

        if ($value !== null) {
            $foreign_class_primary_key = $this->foreignClassPrimaryKey();
            $this->instance->$foreign_key = $value->$foreign_class_primary_key;
        } else {
            $this->instance->$foreign_key = null;
        }
    }

    public function isInitialized(): bool
    {
        return $this->property->isInitialized($this);
    }

    public function foreignClass(): string
    {
        $property_type = $this->property->getType();

        if (!$property_type instanceof \ReflectionNamedType) {
            throw new \Exception('oups');
        }

        return $property_type->getName();
    }

    public function foreignClassPrimaryKey(): string
    {
        $foreign_class = $this->foreignClass();

        if (!is_callable([$foreign_class, 'primaryKeyColumn'])) {
            throw new \Exception('oups');
        }

        return $foreign_class::primaryKeyColumn();
    }

    public function foreignKey(): string
    {
        return $this->association->column;
    }

    public function foreignKeyValue(): mixed
    {
        $foreign_key = $this->foreignKey();
        return $this->instance->$foreign_key;
    }

    public function isNullable(): bool
    {
        $property_type = $this->property->getType();
        return $property_type ? $property_type->allowsNull() : false;
    }
}
