<?php
define('ROOT', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('APP', ROOT . 'application' . DIRECTORY_SEPARATOR);
define('VENDOR', APP . 'vendor' . DIRECTORY_SEPARATOR);

require VENDOR . 'autoload.php';
(new \App\Core\Core())->run();
