<?php

namespace App\Autowiring;


interface AutowiringFactoryInterface
{
    /**
     * @return AutowiringFactoryInterface
     */
    public static function getInstance(): AutowiringFactoryInterface;
}
