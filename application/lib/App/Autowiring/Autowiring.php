<?php

namespace App\Autowiring;


use App\Request;

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
     * @var AutowiringFactoryInterface[]
     */
    private static $references = [];

    public function __construct(string $class, string $method)
    {
        $this->class = $class;
        $this->method = $method;
    }

    /**
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
            self::$references[$class] = $class::getInstance();

            return self::$references[$class];
        }

        return null;
    }

    /**
     * @param \ReflectionParameter[] $reflectionParameters
     *
     * @return array
     * @throws \ReflectionException
     */
    private function analyzeParameters(array $reflectionParameters): array
    {
        if (empty($reflectionParameters)) {
            return [];
        }

        $arguments = [];
        $request = Request::getInstance();
        foreach ($reflectionParameters as $parameter) {
            $name = strtolower($parameter->getName());
            $type = $parameter->getType()->getName();
            if (in_array($name, ['get', 'post']) && $type === 'array') {
                $allowsNull = $parameter->getType()->allowsNull();

                if ($name === 'get') {
                    $get = $request->get();
                    $arguments[] = $allowsNull ? $get : (!empty($get) ? $get : []);
                }

                if ($name === 'post') {
                    $post = $request->post();
                    $arguments[] = $allowsNull ? $post : (!empty($post) ? $post : []);
                }

                continue;
            }

            $isBuiltin = $parameter->getType()->isBuiltin();
            if (!$isBuiltin && ($instance = $this->makeInstanceOf($type)) !== null) {
                $arguments[] = $instance;
            }
        }

        return $arguments;
    }

    /**
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
     * @return array
     * @throws \ReflectionException
     */
    private function analyzeMethod(): array
    {
        $reflection = new \ReflectionMethod($this->class, $this->method);

        return $this->analyzeParameters($reflection->getParameters());
    }

    /**
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
