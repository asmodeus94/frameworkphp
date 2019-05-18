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
        $availableCodes = array_flip(Code::getConstants());
        $code = isset($availableCodes[$code]) ? $code : Code::INTERNAL_SERVER_ERROR;
        http_response_code($code);

        return $this;
    }

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setContentType(string $type = Type::DEFAULT_TYPE): AbstractResponse
    {
        $availableTypes = array_flip(Type::getConstants());
        $type = isset($availableTypes[$type]) ? $type : Type::DEFAULT_TYPE;
        header('Content-Type: ' . $type);

        return $this;
    }

    public abstract function send(): string;
}
