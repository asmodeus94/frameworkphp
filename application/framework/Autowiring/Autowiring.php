<?php

namespace App\Autowiring;


use App\Cookie\Cookie;
use App\Helper\Type;
use App\Route;

class Autowiring
{
    /**
     * @var Route
     */
    private $route;

    /**
     * @var string
     */
    private $class;

    /**
     * @var string
     */
    private $method;

    /**
     * @var array
     */
    private $interfaceRules = [];

    /**
     * @var Cache
     */
    private $cache;

    public function __construct(string $class, string $method)
    {
        $this->class = $class;
        $this->method = $method;
        $this->loadRulesForInterfaces();
        $this->cache = new Cache();
    }

    /**
     * @param Route $route
     *
     * @return $this
     */
    public function setRoute(Route $route): Autowiring
    {
        $this->route = $route;

        return $this;
    }

    /**
     * Ładuje dodatkowe reguły dla typów interfejsowych
     */
    private function loadRulesForInterfaces(): void
    {
        $rulesForInterfaces = [];
        $autowiringFiles = array_diff(scandir(AUTOWIRING . 'interfaces'), ['.', '..']);
        foreach ($autowiringFiles as $autowiringFile) {
            if (strpos($autowiringFile, '.php') === false) {
                continue;
            }

            $tmp = require AUTOWIRING . 'interfaces' . DIRECTORY_SEPARATOR . $autowiringFile;
            $rulesForInterfaces = array_merge($rulesForInterfaces, $tmp);
        }

        $this->interfaceRules = $rulesForInterfaces;
    }

    /**
     * Sprawdza czy podana zmienna odpowiada tablicy get lub post
     *
     * @param \ReflectionParameter $parameter
     *
     * @return array|null|false
     */
    private function isGetOrPost(\ReflectionParameter $parameter)
    {
        $type = $parameter->getType()->getName();
        $allowsNull = $parameter->getType()->allowsNull();
        $name = strtolower($parameter->getName());
        if (in_array($name, ['get', 'post']) && $type === 'array') {
            if ($name === 'get') {
                $get = $this->route->getRequest()->get();
                return $allowsNull ? $get : (!empty($get) ? $get : []);
            }

            if ($name === 'post') {
                $post = $this->route->getRequest()->post();
                return $allowsNull ? $post : (!empty($post) ? $post : []);
            }
        }

        return false;
    }

    /**
     * Sprawdza czy podana zmienna odpowiada ciastku/ciastkom
     *
     * @param \ReflectionParameter $parameter
     *
     * @return array|Cookie|null|false
     */
    private function areCookies(\ReflectionParameter $parameter)
    {
        $type = $parameter->getType()->getName();
        $allowsNull = $parameter->getType()->allowsNull();
        $name = $parameter->getName();
        if (in_array($type, ['array', Cookie::class])) {
            $cookies = $this->route->getRequest()->cookies();
            if (in_array(strtolower($name), ['cookie', 'cookies']) && $type === 'array') {
                return $allowsNull ? $cookies : (!empty($cookies) ? $cookies : []);
            }

            if ($type === Cookie::class && ($allowsNull || isset($cookies[$name]))) {
                return isset($cookies[$name]) ? $cookies[$name] : null;
            }
        }

        return false;
    }

    /**
     * Analizuje parametr typu wbudowanego szukając parametru o takiej samej nazwie w tablicach get oraz post
     *
     * @param \ReflectionParameter $parameter
     *
     * @return mixed
     * @throws \ReflectionException
     */
    private function analyzeBuiltinParameter(\ReflectionParameter $parameter)
    {
        $type = $parameter->getType()->getName();
        $name = $parameter->getName();
        if (($getOrPost = $this->isGetOrPost($parameter)) !== false) {
            return $getOrPost;
        }

        if (($parameterFromRequest = $this->route->getRequest()->getParameter($name)) !== null) {
            if ($type !== gettype($parameterFromRequest)) {
                $parameterFromRequest = Type::cast($parameterFromRequest, $type);
            }

            return $parameterFromRequest;
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        return null;
    }

    /**
     * Analizuje przekazane parametry
     *
     * @param \ReflectionParameter[] $reflectionParameters
     * @param string                 $invoker   Nazwa klasy dla której wywołana została analiza parametrów
     * @param bool                   $forMethod Analiza dla metod (true) poszerzona o typy wbudowane
     *
     * @return array
     * @throws \ReflectionException|\RuntimeException
     */
    private function analyzeParameters(array $reflectionParameters, string $invoker, bool $forMethod = false): array
    {
        if (empty($reflectionParameters)) {
            return [];
        }

        $arguments = [];
        foreach ($reflectionParameters as $parameter) {
            if (($type = $parameter->getType()) === null) {
                $arguments[] = null;

                continue;
            }

            $type = $type->getName();
            $isBuiltin = $parameter->getType()->isBuiltin();

            if ($invoker === $this->class) {
                if (($cookies = $this->areCookies($parameter)) !== false) {
                    $arguments[] = $cookies;

                    continue;
                }

                if ($forMethod && $isBuiltin) {
                    $arguments[] = $this->analyzeBuiltinParameter($parameter);

                    continue;
                }
            }

            if (!$isBuiltin && $type !== Cookie::class && ($instance = $this->makeInstance($type, $invoker)) !== null) {
                $arguments[] = $instance;

                continue;
            }

            throw new \RuntimeException(sprintf('Cannot resolve %s type for %s class', $type, $invoker));
        }

        return $arguments;
    }

    /**
     * Analizuje parametry konstruktora
     *
     * @return array
     * @throws \ReflectionException|\RuntimeException
     */
    private function analyzeConstructor(): array
    {
        $reflection = new \ReflectionClass($this->class);

        return $this->analyzeParameters($reflection->getConstructor()->getParameters(), $this->class);
    }

    /**
     * Analizuje parametry metody
     *
     * @return array
     * @throws \ReflectionException|\RuntimeException
     */
    private function analyzeMethod(): array
    {
        $reflection = new \ReflectionMethod($this->class, $this->method);

        return $this->analyzeParameters($reflection->getParameters(), $this->class, true);
    }

    /**
     * Tworzy obiekt podanego typu, a w przypadku typów interfejsowych korzysta z reguł wiązania interfejsów z
     * odpowiednimi klasamia w zależności od klasy w której zachodzi wiązanie
     *
     * @param string      $className Nazwa klasy której obiekt należy utworzyć
     * @param string|null $invoker   Nazwa klasy agregującej obiekt
     *
     * @return object
     * @throws \ReflectionException|\RuntimeException
     * @see Autowiring::loadRulesForInterfaces()
     */
    private function makeInstance(string $className, ?string $invoker = null): object
    {
        $object = null;
        $hasGetInstance = false;

        if (($object = $this->cache->getByClassName($className)) !== null) {
            return $object;
        }

        if (!class_exists($className) && !interface_exists($className)) {
            return null;
        }

        $reflection = new \ReflectionClass($className);
        if ($invoker !== null && $reflection->isInterface() && isset($this->interfaceRules[$className][$invoker])
            && class_exists($this->interfaceRules[$className][$invoker])) {
            return $this->makeInstance($this->interfaceRules[$className][$invoker], $className);
        }

        $parameters = [];

        if ($reflection->hasMethod('getInstance') && $reflection->getMethod('getInstance')->isStatic()) {
            $object = $className::getInstance();
            $hasGetInstance = true;
        }

        if ($object === null) {
            if (($constructor = $reflection->getConstructor()) !== null && !empty($parameters = $constructor->getParameters())) {
                $parameters = $this->analyzeParameters($parameters, $className);
            }

            if (!empty($parameters)) {
                $object = call_user_func_array([new \ReflectionClass($className), 'newInstance'], $parameters);
            } else {
                $object = new $className();
            }
        }

        $this->cache->add($object, $parameters, $hasGetInstance);

        return $object;
    }

    /**
     * Tworzy obiekty na podstawie danych z cache
     *
     * @param array $data Tablica zawierająca jako klucze:
     *                    - nazwę klasy (className),
     *                    - typy parametrów dla konstruktora (parameters),
     *                    - informację typu logicznego, czy klasa posiada metodę statyczną getInstance (hasGetInstance)
     *
     * @return mixed|null
     * @throws \ReflectionException
     *
     * @see Cache::add()
     */
    private function makeInstanceFromCache(array $data)
    {
        $object = null;
        if (($object = $this->cache->getByClassName($data['className'])) !== null) {
            return $object;
        }

        if (!empty($data['hasGetInstance'])) {
            $object = $data['className']::getInstance();
            $this->cache->add($object, [], true);

            return $object;
        }

        $list = $this->cache->load($this->route->getRoutingRuleName());
        $parameters = [];

        if (!empty($data['parameters'])) {
            foreach ($data['parameters'] as $parameter) {
                $parameters[] = $this->makeInstanceFromCache($list[$parameter]);
            }

            if (!empty($parameters)) {
                $object = call_user_func_array([new \ReflectionClass($data['className']), 'newInstance'], $parameters);
            }
        }

        if ($object === null) {
            $object = new $data['className']();
        }

        $this->cache->add($object, $parameters, false);

        return $object;
    }


    /**
     * Na podstawie nazwy routingu pobiera odpowiednią listę klas i tworzy ich instancje
     *
     * @throws \ReflectionException
     */
    private function loadFromCache(): void
    {
        if (defined('DEBUG')) {
            return;
        }

        $list = $this->cache->load($this->route->getRoutingRuleName());

        foreach ($list as $className => $data) {
            $this->makeInstanceFromCache($data);
        }
    }

    /**
     * Dla podanego routingu zapisuje listę klas
     */
    private function saveToCache(): void
    {
        if (defined('DEBUG')) {
            return;
        }

        $this->cache->save($this->route->getRoutingRuleName());
    }

    /**
     * Uruchamia analizę konstruktora oraz metody kontolera
     *
     * @return array
     * @throws \ReflectionException
     */
    public function analyze(): array
    {
        $this->loadFromCache();
        $constructorArguments = $this->analyzeConstructor();
        $methodArguments = $this->analyzeMethod();
        $this->saveToCache();

        return [$constructorArguments, $methodArguments];
    }
}
