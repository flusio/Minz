<?php

namespace AppTest\migrations;

class Migration201912220002Bar
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
