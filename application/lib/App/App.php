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
     * @param string $controller
     * @param string $method
     *
     * @return array
     * @throws \ReflectionException
     */
    private function prepareArguments(string $controller, string $method)
    {
        $arguments = [];
        $reflection = new \ReflectionMethod($controller, $method);
        $params = $reflection->getParameters();
        foreach ($params as $param) {
            $name = strtolower($param->getName());
            if (!in_array($name, ['get', 'post']) || (string)$param->getType() !== 'array') {
                continue;
            }

            $allowsNull = $param->getType()->allowsNull();

            if ($name === 'get') {
                $get = Request::getInstance()->get();
                $arguments[] = $allowsNull ? $get : (!empty($get) ? $get : []);
            }

            if ($name === 'post') {
                $post = Request::getInstance()->post();
                $arguments[] = $allowsNull ? $post : (!empty($post) ? $post : []);
            }
        }

        return $arguments;
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
            $method = $method !== null ? $method : 'index';
            try {
                if ($this->isCallable($class, $method)) {
                    $autowiring = new Autowiring($class, $method);
                    list($constructorArguments, $methodArguments) = $autowiring->analyzeController();

                    $controller = empty($constructorArguments) ? new $class()
                        : call_user_func_array([new \ReflectionClass($class), 'newInstance'], $constructorArguments);

                    call_user_func_array([$controller, $method], $methodArguments);
                    $responseCode = 200;
                }
            } catch (\Exception $ex) {
                $responseCode = 500;
            }
        }

        if (!isset($responseCode)) {
            $responseCode = 404;
        }

        http_response_code($responseCode);
    }
}
