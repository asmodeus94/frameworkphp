<?php

namespace App\Core;


use App\Helper\ServerHelper;

class Environment
{
    public static function init()
    {
        if (getenv('ENVIRONMENT') === 'development' || ServerHelper::isCli()) {
            define('DEBUG', 1);
        }

        self::setPaths();
        self::setPhpConfig();
    }

    /**
     * Ustawia ścieżki do katalogów projektu
     */
    private static function setPaths()
    {
        define('CONFIG', APP . 'config' . DIRECTORY_SEPARATOR);
        define('CACHE', APP . 'cache' . DIRECTORY_SEPARATOR);
        define('ROUTING', CONFIG . 'routing' . DIRECTORY_SEPARATOR);
        define('AUTOWIRING', CONFIG . 'autowiring' . DIRECTORY_SEPARATOR);

        define('TEMPLATES', APP . 'view' . DIRECTORY_SEPARATOR);

        define('LOGS', APP . 'logs' . DIRECTORY_SEPARATOR);

        define('PUBLIC_DIR', ROOT . 'public' . DIRECTORY_SEPARATOR);
        define('UPLOAD', PUBLIC_DIR . 'upload' . DIRECTORY_SEPARATOR);
    }

    /**
     * Ustawia zmienne konfiguracyjne php
     */
    private static function setPhpConfig()
    {
        if (defined('DEBUG')) {
            error_reporting(E_ALL);
            ini_set("display_errors", 1);
        } else {
            error_reporting(E_ALL & ~E_NOTICE);
            ini_set("display_errors", 0);
        }

        ini_set('session.name', 'session');

        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', 1);

        ini_set('xdebug.var_display_max_depth', -1);
        ini_set('xdebug.var_display_max_children', -1);
        ini_set('xdebug.var_display_max_data', -1);
    }
}
