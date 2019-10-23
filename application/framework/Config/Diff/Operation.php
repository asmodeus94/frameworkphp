<?php

namespace App\Config\Diff;


class Operation
{
    /**
     * Możliwe typy:
     * - add
     * - update
     * - remove
     *
     * @var string
     *
     * @see Type
     */
    private $type;

    /**
     * @var mixed
     */
    private $oldValue;

    /**
     * @var mixed
     */
    private $newValue;

    /**
     * @var string|null
     */
    private $path;

    /**
     * Operation constructor.
     *
     * @param string      $type
     * @param mixed       $oldValue
     * @param mixed       $newValue
     * @param string|null $path
     */
    public function __construct(string $type, $oldValue, $newValue, ?string $path)
    {
        $this->type = $type;
        $this->oldValue = $oldValue;
        $this->newValue = $newValue;
        $this->path = !empty($path) ? $path : null;
    }

    /**
     * @return string
     *
     * @see Type
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getOldValue()
    {
        return $this->oldValue;
    }

    /**
     * @return mixed
     */
    public function getNewValue()
    {
        return $this->newValue;
    }

    /**
     * @return string|null
     */
    public function getPath(): ?string
    {
        return $this->path;
    }
}
