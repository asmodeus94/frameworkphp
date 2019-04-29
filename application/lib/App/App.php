<?php

namespace App;


use App\Helper\Url;

class App
{
    /**
     * @var Route
     */
    private $route;

    public function __construct()
    {
        $this->setEnvironment();
        $this->route = Route::getInstance();
        $this->route->setRequest(Request::getInstance());
    }

    /**
     *
     */
    private function setEnvironment()
    {
        define('CONFIG', APP . 'config' . DIRECTORY_SEPARATOR);
        define('ROUTING', CONFIG . 'routing' . DIRECTORY_SEPARATOR);
        define('LIB', APP . 'lib' . DIRECTORY_SEPARATOR);

        if (!empty($environment = getenv('ENVIRONMENT'))) {
            define('ENVIRONMENT', $environment);
        }

        if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
            error_reporting(E_ALL);
            ini_set("display_errors", 1);
        } else {
            ini_set("display_errors", 0);
        }

        ini_set('xdebug.var_display_max_depth', -1);
        ini_set('xdebug.var_display_max_children', -1);
        ini_set('xdebug.var_display_max_data', -1);
    }

    public function run()
    {
        list($class, $method) = $this->route->run();
        var_dump(Url::make('basic-article', 'dashboard/dsadsadd/page/3/add/super/asd?ad=da'));
    }
}
