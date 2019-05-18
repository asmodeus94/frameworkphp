<?php

namespace App;


use App\Response\Code;

class Redirect
{
    /**
     * @var string
     */
    private $url;

    /**
     * @var int
     */
    private $code;

    public function __construct(string $url)
    {
        $this->url = $url;
    }

    /**
     * @param int $code
     *
     * @return $this
     */
    public function setCode(int $code): Redirect
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Przekierowuje na wybrany adres
     */
    public function make()
    {
        $code = in_array($this->code, [Code::MOVED_PERMANENTLY, Code::MOVED_TEMPORARILY]) ? $this->code : Code::MOVED_PERMANENTLY;
        header('Location: ' . $this->url, true, $code);
        exit;
    }
}
