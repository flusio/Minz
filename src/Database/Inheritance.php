<?php

// This file is part of Minz.
// Copyright 2020-2026 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Database;

use Minz\Errors;

/**
 * Allow several classes to share the same database table.
 *
 * It must be declared on the class using the Recordable trait, alongside the
 * Table attribute. The column contains the type of each row, and the classes
 * parameter gives the class corresponding to each type:
 *
 *     use Minz\Database;
 *
 *     #[Database\Table(name: 'pets')]
 *     #[Database\Inheritance(column: 'type', classes: [
 *         'cat' => Cat::class,
 *         'dog' => Dog::class,
 *     ])]
 *     abstract class Pet
 *     {
 *         use Database\Recordable;
 *
 *         #[Database\Column]
 *         public int $id;
 *
 *         #[Database\Column]
 *         public string $type;
 *
 *         #[Database\Column]
 *         public string $name;
 *     }
 *
 *     class Cat extends Pet
 *     {
 *         #[Database\Column]
 *         public int $lives = 9;
 *     }
 *
 *     class Dog extends Pet
 *     {
 *         #[Database\Column]
 *         public bool $is_playful = true;
 *     }
 *
 * The Recordable methods then behave as follow:
 *
 * - the models are built with the class corresponding to their type:
 *   `Pet::find($id)` returns a Cat or a Dog;
 * - the methods called on a child class only consider the rows of this class
 *   and of its own children: `Cat::find($id)` returns null if the id
 *   corresponds to a Dog, and `Cat::deleteAll()` deletes the Cats only;
 * - the type of a saved model is set from its class;
 * - `Pet::create(['type' => 'cat'])` creates a Cat.
 *
 * The columns declared by another class of the hierarchy are ignored when
 * building a model: the Cat class doesn't need to declare the is_playful column.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class Inheritance
{
    /** @var literal-string */
    public string $column;

    /** @var array<string, class-string> */
    public array $classes;

    /**
     * Cache (by class names) the columns declared only by the other classes of
     * the hierarchy.
     *
     * @var array<class-string, array<string, mixed>>
     */
    private static array $foreign_columns = [];

    /**
     * Cache the instances of the Inheritance attribute by class names.
     *
     * @var array<class-string, ?self>
     */
    private static array $instances = [];

    /**
     * @param literal-string $column
     * @param array<string, class-string> $classes
     */
    public function __construct(string $column, array $classes)
    {
        $this->column = $column;
        $this->classes = $classes;
    }

    /**
     * Return the attribute declared by the given class or by one of its
     * parents, if any.
     *
     * @param class-string $class_name
     */
    public static function fromClass(string $class_name): ?self
    {
        if (array_key_exists($class_name, self::$instances)) {
            return self::$instances[$class_name];
        }

        $inheritance = null;
        $reflection = new \ReflectionClass($class_name);

        while ($reflection) {
            $attributes = $reflection->getAttributes(self::class);

            if ($attributes) {
                $inheritance = $attributes[0]->newInstance();
                break;
            }

            $reflection = $reflection->getParentClass();
        }

        self::$instances[$class_name] = $inheritance;

        return $inheritance;
    }

    /**
     * Return the type corresponding to the given class, if any.
     *
     * @param class-string $class_name
     */
    public function typeOf(string $class_name): ?string
    {
        $type = array_search($class_name, $this->classes);
        return $type === false ? null : strval($type);
    }

    /**
     * Return the types of the rows that the given class can represent, i.e.
     * its own type and the types of its children.
     *
     * @param class-string $class_name
     *
     * @return string[]
     */
    public function typesFor(string $class_name): array
    {
        $types = [];

        foreach ($this->classes as $type => $child_class_name) {
            if (is_a($child_class_name, $class_name, true)) {
                $types[] = strval($type);
            }
        }

        return $types;
    }

    /**
     * Return the class to use for the given type, from the given calling class.
     *
     * If the type is null, the calling class is used.
     *
     * @param class-string $class_name
     *
     * @throws Errors\DatabaseModelError
     *     If the calling class is not declared in the attribute, if the type
     *     is unknown, or if the calling class cannot represent it.
     *
     * @return class-string
     */
    public function resolveClass(string $class_name, mixed $type): string
    {
        if (!$this->typesFor($class_name)) {
            throw new Errors\DatabaseModelError(
                "{$class_name} is not declared in the Inheritance attribute"
            );
        }

        if ($type === null) {
            if ($this->typeOf($class_name) === null) {
                throw new Errors\DatabaseModelError(
                    "{$class_name} requires a value for the {$this->column} column"
                );
            }

            return $class_name;
        }

        if (!is_string($type) || !isset($this->classes[$type])) {
            throw new Errors\DatabaseModelError(
                "{$class_name} doesn't know the {$this->column} column value"
            );
        }

        $type_class_name = $this->classes[$type];

        if (!is_a($type_class_name, $class_name, true)) {
            throw new Errors\DatabaseModelError(
                "{$class_name} cannot represent a model of type {$type}"
            );
        }

        return $type_class_name;
    }

    /**
     * Remove from a row the columns declared only by the other classes of the
     * hierarchy.
     *
     * @param class-string $class_name
     * @param array<mixed, mixed> $row
     *
     * @return array<mixed, mixed>
     */
    public function removeForeignColumns(string $class_name, array $row): array
    {
        if (!isset(self::$foreign_columns[$class_name])) {
            $foreign_columns = [];

            foreach ($this->classes as $other_class_name) {
                $foreign_columns += $other_class_name::databaseColumns();
            }

            self::$foreign_columns[$class_name] = array_diff_key(
                $foreign_columns,
                $class_name::databaseColumns(),
            );
        }

        return array_diff_key($row, self::$foreign_columns[$class_name]);
    }
}
