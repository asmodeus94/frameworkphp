<?php

namespace App\Response;


class Json extends AbstractResponse
{
    /**
     * @var array|string
     */
    private $data;

    /**
     * Json constructor.
     *
     * @param array|string $data
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function encode(): string
    {
        if (is_array($this->data)) {
            $this->data = json_encode($this->data);
        }

        return $this->data;
    }

    /**
     * @return array
     */
    public function decode(): array
    {
        if (is_string($this->data)) {
            $this->data = json_decode($this->data, true);
        }

        return $this->data;
    }

    public function send(): string
    {
        return $this->data;
    }
}
