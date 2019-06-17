<?php

namespace App\Autowiring;


class Cache
{
    /**
     * @var object[]
     */
    private $objectReferences = [];

    /**
     * @param string $className
     *
     * @return mixed|null
     */
    public function getBy(string $className)
    {
        return isset($this->objectReferences[$className]) ? $this->objectReferences[$className] : null;
    }

    /**
     * @param object $object
     */
    public function add(object $object)
    {
        $className = get_class($object);
        $this->objectReferences[$className] = $object;
    }
}
