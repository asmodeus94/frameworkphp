<?php

namespace App\Response;


abstract class AbstractResponse
{
    /**
     * @param int $code
     *
     * @return $this
     */
    public function setCode(int $code): AbstractResponse
    {
        http_response_code($code);

        return $this;
    }

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setContentType(string $type): AbstractResponse
    {
        header('Content-Type: ' . $type);

        return $this;
    }

    public abstract function send(): string;
}
