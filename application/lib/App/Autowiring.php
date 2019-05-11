<?php

namespace App;


use App\Cookie\Cookie;
use App\Helper\Type;

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
     * @var \stdClass[]
     */
    private $references = [];

    public function __construct(string $class, string $method)
    {
        $this->class = $class;
        $this->method = $method;
        $this->loadRulesForInterfaces();
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
        $autowiringFiles = array_diff(scandir(AUTOWIRING), ['.', '..']);
        foreach ($autowiringFiles as $autowiringFile) {
            if (strpos($autowiringFile, '.php') === false) {
                continue;
            }

            $tmp = require AUTOWIRING . $autowiringFile;
            $rulesForInterfaces = array_merge($rulesForInterfaces, $tmp);
        }

        $this->interfaceRules = $rulesForInterfaces;
    }

    /**
     * Tworzy obiekt podanego typu, a w przypadku typów interfejsowych korzysta z reguł wiązania interfejsów z
     * odpowiednimi klasamia w zależności od klasy w której zachodzi wiązanie
     *
     * @param string $className
     * @param string $invoker
     *
     * @return \stdClass
     * @throws \ReflectionException
     *
     * @see Autowiring::loadRulesForInterfaces()
     */
    private function makeInstanceOf(string $className, string $invoker)
    {
        if (isset($this->references[$className])) {
            return $this->references[$className];
        }

        if (!class_exists($className) && !interface_exists($className)) {
            return null;
        }

        $reflection = new \ReflectionClass($className);
        if ($reflection->isInterface() && isset($this->interfaceRules[$className][$invoker])
            && class_exists($this->interfaceRules[$className][$invoker])) {
            return $this->makeInstanceOf($this->interfaceRules[$className][$invoker], $className);
        }

        if ($reflection->hasMethod('getInstance')) {
            return $this->references[$className] = $className::getInstance();
        }

        $parameters = [];
        if (($constructor = $reflection->getConstructor()) !== null && !empty($parameters = $constructor->getParameters())) {
            $parameters = $this->analyzeParameters($parameters, $className);
        }

        if (!empty($parameters)) {
            $class = call_user_func_array([new \ReflectionClass($className), 'newInstance'], $parameters);
        } else {
            $class = new $className();
        }

        return $this->references[$className] = $class;
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
     * @param string                 $invoker   Nazwa klasy wywołującej analizę parametrów
     * @param bool                   $forMethod Analiza dla metod (true) poszerzona o typy wbudowane
     *
     * @return array
     * @throws \ReflectionException
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

            if (($cookies = $this->areCookies($parameter)) !== false) {
                $arguments[] = $cookies;

                continue;
            }

            if ($forMethod && $isBuiltin) {
                $arguments[] = $this->analyzeBuiltinParameter($parameter);

                continue;
            }

            if (!$isBuiltin && ($instance = $this->makeInstanceOf($type, $invoker)) !== null) {
                $arguments[] = $instance;
            } else {
                $arguments[] = null;
            }
        }

        return $arguments;
    }

    /**
     * Analizuje parametry konstruktora
     *
     * @return array
     * @throws \ReflectionException
     */
    private function analyzeConstructor(): array
    {
        $arguments = [];
        $reflection = new \ReflectionClass($this->class);

        $reflection = $reflection->getConstructor();
        if ($reflection !== null && $reflection->isPublic()) {
            $arguments = $this->analyzeParameters($reflection->getParameters(), $this->class);
        }

        return $arguments;
    }

    /**
     * Analizuje parametry metody
     *
     * @return array
     * @throws \ReflectionException
     */
    private function analyzeMethod(): array
    {
        $reflection = new \ReflectionMethod($this->class, $this->method);

        return $this->analyzeParameters($reflection->getParameters(), $this->class, true);
    }

    /**
     * Uruchamia analizę konstruktora oraz metody kontolera
     *
     * @return array
     * @throws \ReflectionException
     */
    public function analyzeController(): array
    {
        $constructorArguments = $this->analyzeConstructor();
        $methodArguments = $this->analyzeMethod();

        return [$constructorArguments, $methodArguments];
    }
}
