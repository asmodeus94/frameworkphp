<?php

namespace App\Helper\Traits;


trait StatusTrait
{
    /**
     * @var int
     */
    private $status;

    /**
     * @param int $status
     *
     * @return $this
     */
    public function setStatus(int $status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @param int $status
     *
     * @return $this
     */
    public function appendStatus(int $status)
    {
        $this->status |= $status;

        return $this;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }
}
