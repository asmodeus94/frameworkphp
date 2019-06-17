<?php

namespace App\Logger;


use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

class LoggerFactory
{
    /**
     * @param string $name
     * @param int    $level
     * @param int    $maxFiles
     *
     * @return Logger
     */
    public static function create(string $name, int $level = Logger::DEBUG, int $maxFiles = 10): Logger
    {
        $handler = new RotatingFileHandler(LOGS . $name . DIRECTORY_SEPARATOR . $name, $maxFiles, $level);
        $handler->setFormatter(new LineFormatter(null, null, true, true));

        return new Logger($name, [$handler]);
    }
}
