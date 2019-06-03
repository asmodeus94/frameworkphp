<?php
if (php_sapi_name() !== 'cli') {
    exit;
}

echo __FILE__;
//$filesystem = new \Symfony\Component\Filesystem\Filesystem();
//$filesystem->remove($files);
