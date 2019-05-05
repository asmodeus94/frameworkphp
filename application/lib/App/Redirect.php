<?php

namespace App;


class Redirect
{
    /**
     * @var string
     */
    private $url;

    public function __construct(string $url)
    {
        $this->url = $url;
    }

    public function make()
    {
        header('location: ' . $this->url);
        exit;
    }
}
