<?php

namespace Minz\Database\Relation;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class BelongsTo extends Association
{
    public function load(\Minz\Database\Relation $relation): mixed
    {
        $foreign_class = $relation->foreignClass();
        $foreign_key_value = $relation->foreignKeyValue();
        $is_nullable = $relation->isNullable();

        if (!is_callable([$foreign_class, 'require'])) {
            throw new \Exception('oups');
        }

        if ($is_nullable && $foreign_key_value === null) {
            $foreign_value = null;
        } else {
            $foreign_value = $foreign_class::require($foreign_key_value);
        }

        if (!$foreign_value instanceof $foreign_class && $foreign_value !== null) {
            throw new \Exception('oups');
        }

        $relation->setForeignValue($foreign_value);

        return $foreign_value;
    }
}
