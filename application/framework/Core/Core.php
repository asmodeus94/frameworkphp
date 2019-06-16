<?php

namespace App\Core;


use App\Helper\ServerHelper;
use App\Redirect;
use App\Request;
use App\Response\AbstractResponse;
use App\Response\Code;
use App\Route\Route;
use App\Autowiring\Autowiring;
use Monolog\Logger;

class Core
{
    /**
     * @var Route
     */
    private $route;

    public function __construct()
    {
        $this->setPaths();
        $this->setEnvironment();
    }

    /**
     * Ustawia stałe zawierające ścieżki do katalogów
     */
    private function setPaths(): void
    {
        define('CONFIG', APP . 'config' . DIRECTORY_SEPARATOR);
        define('CACHE', APP . 'cache' . DIRECTORY_SEPARATOR);
        define('ROUTING', CONFIG . 'routing' . DIRECTORY_SEPARATOR);
        define('AUTOWIRING', CONFIG . 'autowiring' . DIRECTORY_SEPARATOR);

        define('CACHE_AUTOWIRING', CACHE . 'autowiring' . DIRECTORY_SEPARATOR);

        define('TEMPLATES', APP . 'view' . DIRECTORY_SEPARATOR);

        define('LOGS', APP . 'logs' . DIRECTORY_SEPARATOR);
    }

    /**
     * Ustawia zmienne środowiskowe
     */
    private function setEnvironment(): void
    {
        if (getenv('ENVIRONMENT') === 'development' || ServerHelper::isCli()) {
            define('DEBUG', 1);
        }

        if (defined('DEBUG')) {
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
        if ($reflection->isAbstract() || $reflection->getConstructor() === null || !$reflection->getConstructor()->isPublic()
            || !$reflection->isSubclassOf('App\AbstractController') || !$reflection->hasMethod($method)) {
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
     * @throws \RuntimeException|\Error|\ReflectionException
     */
    private function callController(string $class, string $method, array $arguments)
    {
        [$constructorArguments, $methodArguments] = $arguments;

        $controller = empty($constructorArguments) ? new $class()
            : call_user_func_array([new \ReflectionClass($class), 'newInstance'], $constructorArguments);

        if (!empty($methodArguments)) {
            $response = call_user_func_array([$controller, $method], $methodArguments);
        } else {
            $response = $controller->{$method}();
        }

        if (!($response instanceof AbstractResponse) && !($response instanceof Redirect)) {
            throw new \RuntimeException('Response is not an instance of AbstractResponse/Redirect class');
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
    public function run(): void
    {
        $this->route = Route::getInstance();
        $this->route->setRequest(Request::getInstance());

        [$class, $method] = $this->route->run();
        $hasResponse = false;
        $responseCode = null;

        if ($class !== null) {
            try {
                if ($this->isCallable($class, $method)) {
                    $autowiring = new Autowiring($class, $method);
                    $autowiring->setRoute($this->route);
                    $arguments = $autowiring->analyze();
                    $this->callController($class, $method, $arguments);
                    $hasResponse = true;
                }
            } catch (\Exception $ex) {
                // todo: Dodać logowanie błędów (monolog)
                var_dump($ex);
                $responseCode = Code::INTERNAL_SERVER_ERROR;
            } catch (\Error $er) {
                var_dump($er);
                $responseCode = Code::INTERNAL_SERVER_ERROR;
            }
        }

        if ($hasResponse) {
            return;
        } elseif (!$hasResponse && ServerHelper::isCli()) {
            echo 'No endpoint selected!';
        }

        if ($responseCode === null) {
            $responseCode = Code::NOT_FOUND;
        }

        http_response_code($responseCode);
    }
}
