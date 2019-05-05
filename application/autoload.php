<?php

function autoload($className)
{
    $className = LIB . str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';
    require $className;
}

spl_autoload_register('autoload');
