<?php

namespace App\Logger;


class Logger
{
    /**
     * @var \Monolog\Logger[]
     */
    private static $loggers = [];

    /**
     * @param string   $name
     * @param int|null $level
     *
     * @return \Monolog\Logger
     */
    private static function get(string $name, ?int $level = null): \Monolog\Logger
    {
        $level = $level ?? \Monolog\Logger::DEBUG;
        $id = md5($name . $level);
        if (!isset(self::$loggers[$id])) {
            self::$loggers[$id] = LoggerFactory::create($name, $level);
        }

        return self::$loggers[$id];
    }

    /**
     * @param int|null $level
     *
     * @return \Monolog\Logger
     */
    public static function core(?int $level = null): \Monolog\Logger
    {
        return self::get('core', $level);
    }

    /**
     * @param int|null $level
     *
     * @return \Monolog\Logger
     */
    public static function db(?int $level = null): \Monolog\Logger
    {
        return self::get('db', $level);
    }
}
