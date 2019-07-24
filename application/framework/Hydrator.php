<?php

namespace App;


use Zend\Hydrator\ClassMethodsHydrator;
use Zend\Hydrator\Strategy\BooleanStrategy;

class Hydrator
{
    /**
     * Mapowanie wartości logicznych (true/false) na ich odpowiedniki w bazie danych
     */
    const TRUE_VALUE_STRING = 'y';
    const FALSE_VALUE_STRING = 'n';

    /**
     * @var ClassMethodsHydrator
     */
    private $hydrator;

    public function __construct()
    {
        $this->hydrator = new ClassMethodsHydrator(false);
    }

    /**
     * Zapełnia i zwraca podany obiekt danymi z tablicy. W przypadku podania tablicy tablic tworzy kopie podanego
     * obiektu i każdy z nich wypełnia danymi z tablicy, zwracając tablicę obiektów
     *
     * @param array  $data
     * @param object $object
     *
     * @return mixed
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

    /**
     * Oznacza wybrane właściwości obiektu jako typ logiczny wymagający przemapowania
     *
     * @param string|string[] $names
     *
     * @return Hydrator
     */
    public function markBooleanValue($names): Hydrator
    {
        if (!is_array($names)) {
            $names = [$names];
        }

        foreach ($names as $name) {
            foreach (['has', 'is', ''] as $method) {
                $this->hydrator->addStrategy(
                    ($method !== '' ? $method . ucfirst($name) : $name),
                    new BooleanStrategy(self::TRUE_VALUE_STRING, self::FALSE_VALUE_STRING)
                );
            }
        }

        return $this;
    }
}
