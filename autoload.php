<?php

spl_autoload_register(function ($class_name) {
    if (str_starts_with($class_name, 'Minz')) {
        $class_name = substr($class_name, 5);
        include(__DIR__ . '/src/' . str_replace('\\', '/', $class_name) . '.php');
    } elseif (str_starts_with($class_name, 'PHPMailer')) {
        $class_name = substr($class_name, 20);
        include(__DIR__ . '/lib/PHPMailer/src/' . str_replace('\\', '/', $class_name) . '.php');
    } elseif (str_starts_with($class_name, 'AppTest')) {
        $class_name = substr($class_name, 8);
        $class_path = str_replace('\\', '/', $class_name) . '.php';
        @include(__DIR__ . '/tests/test_app/src/' . $class_path);
    }
});
