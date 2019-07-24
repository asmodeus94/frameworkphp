<?php

namespace View;


use App\Helper\BitMaskHelper;
use View\Alerts\Alert;
use View\Alerts\Type;

class Alerts
{
    /**
     * @var BitMaskHelper
     */
    private $bitMask;

    /**
     * @var array
     */
    private $messages;

    /**
     * Alerts constructor.
     *
     * @param int|null $statusCode
     */
    public function __construct(?int $statusCode)
    {
        $this->bitMask = new BitMaskHelper($statusCode);
        $this->messages = [];
    }

    /**
     * @param int   $type
     * @param array $messages
     *
     * @return $this
     * @see Type
     */
    public function add(int $type, array $messages): Alerts
    {
        $this->messages[$type] = $messages;

        return $this;
    }

    /**
     * @return Alert[]
     */
    public function make(): array
    {
        $alerts = [];
        foreach ($this->messages as $type => $messages) {
            foreach ($messages as $code => $message) {
                if (!$this->bitMask->contains($code)) {
                    continue;
                }

                $alerts[] = (new Alert())
                    ->setType($type)
                    ->setMessage($message);
            }
        }

        return $alerts;
    }
}
