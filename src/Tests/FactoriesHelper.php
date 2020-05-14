<?php

namespace Minz\Tests;

/**
 * Provide a create() method that delegates the work to a factory
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait FactoriesHelper
{
    /**
     * @param string $factory_name
     * @param array $values default is []
     *
     * @return integer|string|boolean Return the result of DatabaseModel::create
     *
     * @see \Minz\DatabaseModel
     */
    public function create($factory_name, $values = [])
    {
        $factory = new DatabaseFactory($factory_name);
        return $factory->create($values);
    }
}
