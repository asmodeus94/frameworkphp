<?php

namespace App\Autowiring;


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
     * @param string $name       Nazwa zmiennej
     * @param string $type       Typ zmiennej
     * @param bool   $allowsNull Czy może być null
     *
     * @return array|null|false
     */
    private function isGetOrPost(string $name, string $type, bool $allowsNull)
    {
        $name = strtolower($name);
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
     * Analizuje parametr typu wbudowanego szukając parametru o takiej samej nazwie w tablicach get oraz post
     *
     * @param \ReflectionParameter $parameter
     *
     * @return mixed
     */
    private function analyzeBuiltinParameter(\ReflectionParameter $parameter)
    {
        $type = $parameter->getType();
        $name = $parameter->getName();
        $allowsNull = $parameter->getType()->allowsNull();
        if (($getOrPost = $this->isGetOrPost($name, $type, $allowsNull)) !== false) {
            return $getOrPost;
        }

        if (($parameterFromRequest = $this->route->getRequest()->getParameter($name)) !== null) {
            if ($type !== gettype($parameterFromRequest)) {
                $parameterFromRequest = Type::cast($parameterFromRequest, $type);
            }

            return $parameterFromRequest;
        }

        if ($allowsNull) {
            return null;
        }

        return false;
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

            if ($forMethod && $isBuiltin) {
                if (($builtinParameter = $this->analyzeBuiltinParameter($parameter)) !== false) {
                    $arguments[] = $builtinParameter;
                }

                continue;
            }

            if (!$isBuiltin && ($instance = $this->makeInstanceOf($type)) !== null) {
                $arguments[] = $instance;
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
