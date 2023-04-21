<?php

namespace AppTest;

use Minz\Request;
use Minz\Response;

class Migrations extends \Minz\Migration\Controller
{
    public static function schemaPath(): string
    {
        assert(\Minz\Configuration::$database !== null);

        $database_type = \Minz\Configuration::$database['type'];
        return \Minz\Configuration::$app_path . "/schema.{$database_type}.sql";
    }
}
