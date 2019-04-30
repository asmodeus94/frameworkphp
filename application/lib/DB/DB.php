<?php

namespace DB;


use App\Autowiring\AutowiringFactoryInterface;

class DB implements AutowiringFactoryInterface
{
    private static $instance = null;

    /**
     * @var string
     */
    private $name;

    private function __construct()
    {
        $this->name = 'ddd';
    }

    public static function getInstance(): AutowiringFactoryInterface
    {
        if (!isset(self::$instance)) {
            self::$instance = new DB();
        }

        return self::$instance;
    }
}