<?php

namespace App;


use App\Autowiring\Autowiring;

class App
{
    /**
     * @var Route
     */
    private $route;

    public function __construct()
    {
        $this->setConstants();
        $this->setEnvironment();
    }

    /**
     * Ustawia stałe
     */
    private function setConstants(): void
    {
        define('CONFIG', APP . 'config' . DIRECTORY_SEPARATOR);
        define('CACHE', APP . 'cache' . DIRECTORY_SEPARATOR);
        define('ROUTING', CONFIG . 'routing' . DIRECTORY_SEPARATOR);

        define('TWIG_CACHE', CACHE . 'twig' . DIRECTORY_SEPARATOR);
    }

    /**
     * Ustawia zmienne środowiskowe
     */
    private function setEnvironment(): void
    {
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

    /**
     * @param string $controller
     * @param string $method
     *
     * @return bool
     * @throws \ReflectionException
     */
    private function isCallable(string $controller, string $method): bool
    {
        $reflection = new \ReflectionClass($controller);
        if ($reflection->isAbstract() || !$reflection->getConstructor()->isPublic()
            || !$reflection->isSubclassOf('App\ControllerAbstract') || !$reflection->hasMethod($method)) {
            return false;
        }

        $reflection = new \ReflectionMethod($controller, $method);

        return $reflection->isPublic() && !$reflection->isStatic();
    }

    /**
     * Uruchamia aplikację poprzez wywołanie metody kontrolera
     */
    public function run()
    {
        $this->route = Route::getInstance();
        $this->route->setRequest(Request::getInstance());

        list($class, $method) = $this->route->run();
        $responseCode = null;

        if ($class !== null) {
            try {
                if ($this->isCallable($class, $method)) {
                    $autowiring = new Autowiring($class, $method);
                    list($constructorArguments, $methodArguments) = $autowiring->analyzeController();

                    $controller = empty($constructorArguments) ? new $class()
                        : call_user_func_array([new \ReflectionClass($class), 'newInstance'], $constructorArguments);

                    if (!empty($methodArguments)) {
                        call_user_func_array([$controller, $method], $methodArguments);
                    } else {
                        $controller->{$method}();
                    }

                    $responseCode = 200;
                }
            } catch (\Exception $ex) {
                // todo: Dodać logowanie błędów (monolog)
                var_dump($ex);
                $responseCode = 500;
            }
        }

        if (!isset($responseCode)) {
            $responseCode = 404;
        }

        http_response_code($responseCode);
    }
}
