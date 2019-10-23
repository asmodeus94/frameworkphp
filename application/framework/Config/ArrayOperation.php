<?php

namespace App\Config;

class ArrayOperation
{
    /**
     * @var array
     */
    private $array;

    /**
     * @var mixed
     */
    private $parent = null;

    /**
     * @var mixed
     */
    private $children = null;

    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $delimiter;

    /**
     * @var int
     */
    private $delimiterLength;

    /**
     * ArrayOperation constructor.
     *
     * @param array  $array
     * @param string $path
     * @param string $delimiter
     */
    public function __construct(array &$array, string $path, $delimiter = ' / ')
    {
        $this->array = &$array;
        $this->path = $path;
        $this->delimiter = $delimiter;
        $this->delimiterLength = strlen($delimiter);
    }

    /**
     * Na podstawie ścieżki i referencji tablicy ustala referencje do elementu kończącego ścieżkę (dziecka/child), a
     * także elementu go poprzedzającego (rodzica/parent) w tablicy
     *
     * @param array  &$array
     * @param string  $path
     */
    private function goToPath(array &$array, string $path): void
    {
        $this->parent = &$array;
        if (($index = stripos($path, $this->delimiter)) !== false) {
            $key = substr($path, 0, $index);
            $path = substr($path, $index + $this->delimiterLength, strlen($path) - $index);

            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }

            $this->goToPath($array[$key], $path);
        } else {
            $this->children = &$array[$path];
        }
    }

    /**
     * Ustawia wartość dla klucza
     *
     * @param mixed $value
     */
    public function set($value): void
    {
        $this->goToPath($this->array, $this->path);
        $this->children = $value;
    }

    /**
     * Usuwa klucz
     */
    public function remove(): void
    {
        $this->goToPath($this->array, $this->path);
        $keys = explode($this->delimiter, $this->path);
        unset($this->parent[end($keys)]);
    }
}
