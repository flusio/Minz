<?php

namespace Minz\Database;

use Minz\Memoizer;

trait Relational
{
    use Memoizer;

    /**
     * @param ?string[] $names
     */
    public function loadRelations(?array $names = null): void
    {
        if ($names === null) {
            $relations = $this->relations();
            $names = array_keys($relations);
        }

        foreach ($names as $name) {
            $this->loadRelation($name);
        }
    }

    public function loadRelation(string $name): mixed
    {
        return $this->memoize("relation_{$name}", function () use ($name): mixed {
            $relation = self::relation($name);
            return $relation->load();
        });
    }

    public function setRelation(string $name, mixed $value): void
    {
        $relation = self::relation($name);
        $relation->set($value);

        $this->memoizeValue("relation_{$name}", $value);
    }

    public function unloadRelation(string $name): void
    {
        $this->unmemoize("relation_{$name}");
    }

    public function reloadRelation(string $name): void
    {
        $this->unloadRelation($name);
        $this->loadRelation($name);
    }

    public function isRelationLoaded(string $name): bool
    {
        return $this->isMemoized("relation_{$name}");
    }

    /**
     * @return array<string, Relation>
     */
    public function relations(): array
    {
        return $this->memoize('relations', function (): array {
            $class_reflection = new \ReflectionClass(static::class);
            $properties = $class_reflection->getProperties();

            $relations = [];

            foreach ($properties as $property) {
                $association_attributes = $property->getAttributes(
                    Relation\Association::class,
                    \ReflectionAttribute::IS_INSTANCEOF
                );

                if (empty($association_attributes)) {
                    continue;
                }

                $association = $association_attributes[0]->newInstance();
                $property_name = $property->getName();
                $property_type = $property->getType();

                if (!($property_type instanceof \ReflectionNamedType)) {
                    throw new \Exception('oups');
                }

                $class = $property_type->getName();

                if (
                    !class_exists($class) ||
                    !is_callable([$class, 'primaryKeyColumn']) ||
                    !is_callable([$class, 'require'])
                ) {
                    throw new \Exception('oups');
                }

                $relations[$property_name] = new Relation($this, $property, $association);
            }

            return $relations;
        });
    }

    public function relation(string $name): Relation
    {
        $relations = self::relations();
        // TODO fail if unknown
        return $relations[$name];
    }
}
