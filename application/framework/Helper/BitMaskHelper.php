<?php

namespace App\Helper;


class BitMaskHelper
{
    /**
     * @var int
     */
    private $bitMask;

    /**
     * BitMaskHelper constructor.
     *
     * @param int $bitMask
     */
    public function __construct(?int $bitMask)
    {
        $this->bitMask = $bitMask;
    }

    /**
     * @param int $status
     *
     * @return bool
     */
    public function contains(int $status): bool
    {
        if ($this->bitMask === null) {
            return false;
        }

        return ($this->bitMask & $status) > 0;
    }
}
