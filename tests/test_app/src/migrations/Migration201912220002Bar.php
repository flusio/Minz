<?php

namespace AppTest\migrations;

class Migration201912220002Bar
{
    /**
     * @return boolean true if the migration was successful, false otherwise
     */
    public function migrate(): bool
    {
        return true;
    }

    /**
     * @return boolean true if the rollback was successful, false otherwise
     */
    public function rollback(): bool
    {
        return true;
    }
}
