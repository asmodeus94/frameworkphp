<?php

namespace App;


use App\Autowiring\Autowiring;
use App\Response\AbstractResponse;

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

        define('TWIG_TEMPLATES', APP . 'view' . DIRECTORY_SEPARATOR);
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
            || !$reflection->isSubclassOf('App\Controller') || !$reflection->hasMethod($method)) {
            return false;
        }

        $reflection = new \ReflectionMethod($controller, $method);

        return $reflection->isPublic() && !$reflection->isStatic();
    }

    /**
     * @param string $class
     * @param string $method
     * @param array  $arguments
     *
     * @throws \Exception|\Error|\ReflectionException
     */
    private function callController(string $class, string $method, array $arguments)
    {
        list($constructorArguments, $methodArguments) = $arguments;

        $controller = empty($constructorArguments) ? new $class()
            : call_user_func_array([new \ReflectionClass($class), 'newInstance'], $constructorArguments);

        if (!empty($methodArguments)) {
            $response = call_user_func_array([$controller, $method], $methodArguments);
        } else {
            $response = $controller->{$method}();
        }

        if (!($response instanceof AbstractResponse) && !($response instanceof Redirect)) {
            throw new \Exception('Response is not an instance of AbstractResponse/Redirect class');
        }

        if ($response instanceof AbstractResponse) {
            echo $response->send();
        }

        if ($response instanceof Redirect) {
            $response->make();
        }
    }

    /**
     * Uruchamia aplikację poprzez wywołanie metody kontrolera
     */
    public function run()
    {
        $this->route = Route::getInstance();
        $this->route->setRequest(Request::getInstance());

        list($class, $method) = $this->route->run();
        $hasResponse = false;
        $responseCode = null;

        if ($class !== null) {
            try {
                if ($this->isCallable($class, $method)) {
                    $autowiring = new Autowiring($class, $method);
                    $autowiring->setRoute($this->route);

                    $arguments = $autowiring->analyzeController();
                    $this->callController($class, $method, $arguments);
                    $hasResponse = true;
                }
            } catch (\Exception $ex) {
                // todo: Dodać logowanie błędów (monolog)
                var_dump($ex);
                $responseCode = 500;
            } catch (\Error $er) {
                var_dump($er);
                $responseCode = 500;
            }
        }

        if (!$hasResponse) {
            if ($responseCode === null) {
                $responseCode = 404;
            }

            http_response_code($responseCode);
        }
    }
}
