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
     * @var AutowiringFactoryInterface[]
     */
    private static $references = [];

    public function __construct(string $class, string $method)
    {
        $this->class = $class;
        $this->method = $method;
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
     * Tworzy obiekt podanej klasy implementującej interfejs fabryki obiektów
     *
     * @param string $class
     *
     * @return AutowiringFactoryInterface|null
     * @throws \ReflectionException
     */
    private function makeInstanceOf(string $class): ?AutowiringFactoryInterface
    {
        if (isset(self::$references[$class])) {
            return self::$references[$class];
        }

        if (!class_exists($class)) {
            return null;
        }

        $reflection = new \ReflectionClass($class);

        if ($reflection->implementsInterface('App\Autowiring\AutowiringFactoryInterface')) {
            /** @var AutowiringFactoryInterface $class */
            self::$references[(string)$class] = $class::getInstance();

            return self::$references[$class];
        }

        return null;
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
     * @param bool                   $forMethod Analiza dla metod (true) poszerzona o typy wbudowane
     *
     * @return array
     * @throws \ReflectionException
     */
    private function analyzeParameters(array $reflectionParameters, bool $forMethod = false): array
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

            if (!$isBuiltin && ($instance = $this->makeInstanceOf($type)) !== null) {
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
            $arguments = $this->analyzeParameters($reflection->getParameters());
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

        return $this->analyzeParameters($reflection->getParameters(), true);
    }

    /**
     * Uruchamia analizę konstruktora oraz metody
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
