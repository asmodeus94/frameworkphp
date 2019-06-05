<?php

namespace App\Autowiring;


class Cache
{
    /**
     * @var object[]
     */
    private $objectReferences = [];

    /**
     * @var string[]
     */
    private $orderedList = [];

    /**
     * @var bool
     */
    private $cached;

    /**
     * @param string $className
     *
     * @return mixed|null
     */
    public function getByClassName(string $className)
    {
        return isset($this->objectReferences[$className]) ? $this->objectReferences[$className] : null;
    }

    /**
     * @param object $object
     * @param array  $parameters
     * @param bool   $hasGetInstance
     */
    public function add(object $object, array $parameters, bool $hasGetInstance)
    {
        $className = get_class($object);
        $this->objectReferences[$className] = $object;

        if (!empty($parameters)) {
            foreach ($parameters as &$parameter) {
                $parameter = get_class($parameter);
            }
        }

        $this->orderedList[$className] = [
            'className' => $className,
            'parameters' => $parameters,
            'hasGetInstance' => $hasGetInstance,
        ];
    }

    /**
     * @return string[]
     */
    public function getOrderedList(): array
    {
        return $this->orderedList;
    }

    /**
     * Ładuje z pliku listę klas
     *
     * @param string $name
     *
     * @return array
     */
    public function load(string $name): array
    {
        $file = CACHE_AUTOWIRING . $name . '.php';
        if (empty($this->orderedList) && $this->cached = file_exists($file)) {
            $this->orderedList = require $file;
        }

        return $this->orderedList;
    }

    /**
     * Zapisuje do pliku listę klas pod warunkiem, że przed żądaniem cache dla podanej nazwy nie istniało
     *
     * @param string $name
     */
    public function save(string $name): void
    {
        if ($this->cached || empty($this->orderedList) || !file_exists(CACHE_AUTOWIRING)) {
            return;
        }

        $file = CACHE_AUTOWIRING . $name . '.php';
        if (!file_exists($file)) {
            $data = '<?php return ' . str_replace([' ', PHP_EOL], ['', ''], var_export($this->orderedList, true)) . ';' . PHP_EOL;
            file_put_contents($file, $data);
        }
    }
}
