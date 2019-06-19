<?php

namespace App;


use Zend\Hydrator\NamingStrategy\UnderscoreNamingStrategy;
use Zend\Hydrator\ObjectPropertyHydrator;

class Hydrator
{
    /**
     * @var ObjectPropertyHydrator
     */
    private $hydrator;

    public function __construct()
    {
        $this->hydrator = new ObjectPropertyHydrator();
        $this->hydrator->setNamingStrategy(new UnderscoreNamingStrategy());
    }

    /**
     * Zapełnia i zwraca podany obiekt danymi z tablicy. W przypadku podania tablicy tablic tworzy kopie podanego
     * obiektu i każdy z nich wypełnia danymi z tablicy, zwracając tablicę obiektów
     *
     * @param array  $data
     * @param object $object
     *
     * @return object|object[]
     */
    public function hydrate(array $data, object $object)
    {
        if (isset($data[0]) && is_array($data[0])) {
            $objects = [$this->hydrator->hydrate($data[0], $object)];
            unset($data[0]);
            foreach ($data as $datum) {
                $objectCopy = clone $object;
                $objects[] = $this->hydrator->hydrate($datum, $objectCopy);
            }

            return $objects;
        }

        return $this->hydrator->hydrate($data, $object);
    }

    /**
     * Na podstawie obiektu lub tablicy obiektów tworzy odpowiadającą im tablicę
     *
     * @param object|object[] $object
     * @param bool            $recursive
     *
     * @return array
     */
    public function extract($object, bool $recursive = false): array
    {
        if (is_object($object)) {
            $object = $this->hydrator->extract($object);
        }

        if ($recursive) {
            foreach ($object as $index => &$objectVar) {
                if (is_object($objectVar) || is_array($objectVar)) {
                    $objectVar = $this->extract($objectVar, $recursive);
                }
            }
        }

        return $object;
    }
}
