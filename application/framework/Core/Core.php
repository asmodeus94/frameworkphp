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
     * @var Logger
     */
    private $logger;

    /**
     * @var Route
     */
    private $route;

    public function __construct()
    {
        Environment::init();
        $this->logger = \App\Logger\Logger::core();
    }

    /**
     * @param string|null $controller
     * @param string|null $method
     *
     * @return bool
     * @throws \ReflectionException
     */
    private function isCallable(?string $controller, ?string $method): bool
    {
        if ($controller === null || $method === null) {
            return false;
        }

        $reflection = new \ReflectionClass($controller);
        if ($reflection->isAbstract() || ($reflection->getConstructor() !== null && !$reflection->getConstructor()->isPublic())
            || !$reflection->isSubclassOf('App\AbstractController') || !$reflection->hasMethod($method)) {
            return false;
        }

        $reflection = new \ReflectionMethod($controller, $method);

        return $reflection->isPublic() && !$reflection->isStatic();
    }

    /**
     * Tworzy kontroler i wywołuje z niego metodę
     *
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
        $hasResponse = $isError = false;
        $responseCode = null;

        try {
            $this->route = Route::getInstance();
            $this->route->setRequest(Request::getInstance());

            [$class, $method] = $this->route->run();

            if ($this->isCallable($class, $method)) {
                $arguments = (new Autowiring($class, $method))->analyze();
                $this->callController($class, $method, $arguments);
                $hasResponse = true;
            }
        } catch (\Doctrine\DBAL\DBALException | \PDOException $ex) {
            \App\Logger\Logger::db()->addCritical($ex);
            $isError = true;
        } catch (\Exception $ex) {
            $this->logger->addError($ex);
            $isError = true;
        } catch (\Error $er) {
            $this->logger->addCritical($er);
            $isError = true;
        }

        if ($isError) {
            $responseCode = Code::INTERNAL_SERVER_ERROR;
        }

        if ($hasResponse) {
            return;
        } elseif (!$hasResponse && ServerHelper::isCli()) {
            echo 'No endpoint selected!';

            return;
        }

        if ($responseCode === null) {
            $responseCode = Code::NOT_FOUND;
        }

        http_response_code($responseCode);
    }
}
