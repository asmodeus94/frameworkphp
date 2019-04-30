<?php
define('ROOT', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('APP', ROOT . 'application' . DIRECTORY_SEPARATOR);
define('LIB', APP . 'lib' . DIRECTORY_SEPARATOR);
define('VENDOR', APP . 'vendor' . DIRECTORY_SEPARATOR);

require VENDOR . 'autoload.php';
require APP . 'autoloader.php';

(new \App\App())->run();
