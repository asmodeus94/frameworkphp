<?php
define('APP', '.' . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR);
define('VENDOR', APP . 'vendor' . DIRECTORY_SEPARATOR);

require VENDOR . 'autoload.php';

\App\Core\Environment::init();

if (!\App\Helper\ServerHelper::isCli()) {
    exit;
}
