<?php

namespace App\Configurator;


class Configurator
{
    /**
     * @var \DB
     */
    private $db;

    public function __construct(\DB $db)
    {
        $this->db = $db;
    }

    public function test()
    {
        return 'chop';
    }
}