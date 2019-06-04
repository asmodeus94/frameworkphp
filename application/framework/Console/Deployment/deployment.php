<?php
if (php_sapi_name() !== 'cli') {
    exit;
}

define('APP', '.' . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR);
define('VENDOR', APP . 'vendor' . DIRECTORY_SEPARATOR);
define('CACHE_AUTOWIRING', APP . 'cache' . DIRECTORY_SEPARATOR . 'autowiring' . DIRECTORY_SEPARATOR);

require VENDOR . 'autoload.php';

$filesystem = new \Symfony\Component\Filesystem\Filesystem();
$filesystem->remove(CACHE_AUTOWIRING);
$filesystem->mkdir(CACHE_AUTOWIRING);
