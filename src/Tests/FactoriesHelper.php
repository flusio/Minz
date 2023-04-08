<?php

namespace Minz\Tests;

/**
 * Provide a create() method that delegates the work to a factory
 *
 * @phpstan-import-type ModelValues from \Minz\Model
 *
 * @phpstan-import-type ModelId from \Minz\Model
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait FactoriesHelper
{
    /**
     * @param string $factory_name
     * @param ModelValues $values
     *
     * @return ModelId|boolean
     *
     * @see \Minz\DatabaseModel::create
     */
    public function create(string $factory_name, array $values = []): mixed
    {
        $factory = new DatabaseFactory($factory_name);
        return $factory->create($values);
    }
}
