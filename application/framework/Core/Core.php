<?php

namespace App\Core;


use App\Api\Exception\ApiUnauthorizedException;
use App\Helper\ServerHelper;
use App\Redirect;
use App\Request;
use App\Response\AbstractResponse;
use App\Response\Code;
use App\Response\DownloadableInterface;
use App\Response\Json;
use App\Route\Route;
use App\Autowiring\Autowiring;
use App\Session;

class Core
{
    /**
     * @var Route
     */
    private $route;

    public function __construct()
    {
        Environment::init();
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
     * @throws \Exception|\Error
     */
    private function callController(string $class, string $method, array $arguments)
    {
        [$constructorArguments, $methodArguments] = $arguments;

        try {
            $controller = empty($constructorArguments) ? new $class()
                : call_user_func_array([new \ReflectionClass($class), 'newInstance'], $constructorArguments);
        } catch (ApiUnauthorizedException $e) {
            $response = (new Json(['errors' => ['Unauthorized']]))->setCode(Code::UNAUTHORIZED);
            $response->encode();
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }

        if (isset($controller)) {
            if (!empty($methodArguments)) {
                $response = call_user_func_array([$controller, $method], $methodArguments);
            } else {
                $response = $controller->{$method}();
            }
        }

        if (!isset($response)) {
            throw new \RuntimeException('Response has not been set');
        }

        $this->handleResponse($response);
    }

    /**
     * Obsługuje odpowiedź z kontrolera
     *
     * @param AbstractResponse|Redirect $response
     *
     * @throws \Exception|\Error
     */
    private function handleResponse($response)
    {
        $instanceOfAbstractResponse = $response instanceof AbstractResponse;
        if (!$instanceOfAbstractResponse && !($response instanceof Redirect)) {
            throw new \RuntimeException('Response is not an instance of AbstractResponse/Redirect class');
        }

        if ($instanceOfAbstractResponse) {
            if (!($response instanceof DownloadableInterface)) {
                echo $response->send();
            } else {
                $response->send();
            }
        } else {
            $response->make();
        }
    }

    /**
     * Uruchamia aplikację poprzez wywołanie metody kontrolera
     */
    public function run(): void
    {
        $hasResponse = false;
        $responseCode = null;

        try {
            $this->route = Route::getInstance()
                ->setRequest(Request::getInstance())
                ->setSession(Session::getInstance());

            [$class, $method] = $this->route->run();

            if ($this->isCallable($class, $method)) {
                $arguments = (new Autowiring($class, $method))->analyze();
                $this->callController($class, $method, $arguments);
                $hasResponse = true;
            }
        } catch (\Doctrine\DBAL\DBALException | \PDOException $e) {
            \App\Logger\Logger::db()->addCritical($e);
        } catch (\Exception $e) {
            \App\Logger\Logger::core()->addError($e);
        } catch (\Error $e) {
            \App\Logger\Logger::core()->addCritical($e);
        }

        if ($hasResponse) {
            return;
        } elseif (ServerHelper::isCLI()) {
            if (isset($e)) {
                echo 'An error occurred: ' . $e;
            } else {
                echo 'No endpoint selected!';
            }

            return;
        } elseif (defined('DEBUG') && isset($e)) {
            throw $e;
        }

        if (isset($e)) {
            $responseCode = Code::INTERNAL_SERVER_ERROR;
        } else {
            $responseCode = Code::NOT_FOUND;
        }

        http_response_code($responseCode);
    }
}
