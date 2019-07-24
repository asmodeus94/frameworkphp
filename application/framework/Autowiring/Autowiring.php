<?php

namespace App\Autowiring;


use App\Cookie\Cookie;
use App\Helper\TypeHelper;
use App\Hydrator;
use App\Route\Route;

class Autowiring
{
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
    private $interfacesRules = [];

    /**
     * @var Cache
     */
    private $cache;

    public function __construct(string $class, string $method)
    {
        $this->class = $class;
        $this->method = $method;

        $this->interfacesRules = $this->loadRules('interfaces');

        $this->cache = new Cache();
    }

    /**
     * @param string $type
     *
     * @return array
     */
    private function loadRules(string $type): array
    {
        $rules = [];
        $autowiringFiles = array_diff(scandir(AUTOWIRING . $type), ['.', '..']);
        foreach ($autowiringFiles as $autowiringFile) {
            if (strpos($autowiringFile, '.php') === false) {
                continue;
            }

            $tmp = require AUTOWIRING . $type . DIRECTORY_SEPARATOR . $autowiringFile;
            $rules = array_merge($rules, $tmp);
        }

        return $rules;
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
                $get = Route::getInstance()->getRequest()->get();
                return $allowsNull ? $get : (!empty($get) ? $get : []);
            }

            if ($name === 'post') {
                $post = Route::getInstance()->getRequest()->post();
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
            $cookies = Route::getInstance()->getRequest()->cookies();
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
        if (($getOrPost = $this->isGetOrPost($parameter)) !== false) {
            return $getOrPost;
        }

        $type = $parameter->getType()->getName();

        if (($parameterFromRequest = Route::getInstance()->getRequest()->getParameter($parameter->getName())) !== null) {
            if ($type === 'bool'
                && ($parameterFromRequest === Hydrator::TRUE_VALUE_STRING || $parameterFromRequest === Hydrator::FALSE_VALUE_STRING)) {
                return $parameterFromRequest === Hydrator::TRUE_VALUE_STRING;
            } elseif ($type === gettype($parameterFromRequest)) {
                return $parameterFromRequest;
            } else {
                return TypeHelper::cast($parameterFromRequest, $type);
            }
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        if (!$parameter->getType()->allowsNull()) {
            if ($type === 'int' || $type === 'float') {
                return 0;
            } elseif ($type === 'bool') {
                return false;
            } elseif ($type === 'array') {
                return [];
            } elseif ($type === 'string') {
                return '';
            }
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

        if (($constructor = $reflection->getConstructor()) === null) {
            return [];
        }

        return $this->analyzeParameters($constructor->getParameters(), $this->class);
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
     * Tworzy obiekt podanego typu, a w przypadku typów interfejsowych korzysta z reguł wiązania interfejsów
     * z odpowiednimi klasami w zależności od klasy w której zachodzi wiązanie
     *
     * @param string      $className Nazwa klasy której obiekt należy utworzyć
     * @param string|null $invoker   Nazwa klasy agregującej obiekt
     *
     * @return object
     * @throws \ReflectionException|\RuntimeException
     */
    private function makeInstance(string $className, ?string $invoker = null): object
    {
        $object = null;

        if (($object = $this->cache->getBy($className)) !== null) {
            return $object;
        }

        if (!class_exists($className) && !interface_exists($className)) {
            return null;
        }

        $parameters = [];

        $reflection = new \ReflectionClass($className);

        if ($reflection->hasMethod('getInstance') && $reflection->getMethod('getInstance')->isStatic()) {
            $object = $className::getInstance();
        }

        if ($invoker !== null && $reflection->isInterface() && isset($this->interfacesRules[$className][$invoker])
            && class_exists($this->interfacesRules[$className][$invoker])) {
            $className = $this->interfacesRules[$className][$invoker];
            $reflection = new \ReflectionClass($className);
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

        $this->cache->add($object);

        return $object;
    }

    /**
     * Uruchamia analizę konstruktora oraz metody kontolera
     *
     * @return array
     * @throws \ReflectionException
     */
    public function analyze(): array
    {
        return [$this->analyzeConstructor(), $this->analyzeMethod()];
    }
}
