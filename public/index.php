<?php
define('ROOT', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('APP', ROOT . 'application' . DIRECTORY_SEPARATOR);
define('CONFIG', APP . 'config' . DIRECTORY_SEPARATOR);
define('ROUTING', CONFIG . 'routing' . DIRECTORY_SEPARATOR);
define('LIB', APP . 'lib' . DIRECTORY_SEPARATOR);

require APP . 'autoloader.php';

(new \App\App())->run();
