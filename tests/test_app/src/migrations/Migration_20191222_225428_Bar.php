<?php

namespace AppTest\migrations;

// phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
class Migration_20191222_225428_Bar
{
    /**
     * @return boolean true if the migration was successful, false otherwise
     */
    public function migrate()
    {
        return true;
    }

    /**
     * @return boolean true if the rollback was successful, false otherwise
     */
    public function rollback()
    {
        return true;
    }
}
