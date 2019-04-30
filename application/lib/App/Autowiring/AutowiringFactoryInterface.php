<?php

namespace App\Autowiring;


interface AutowiringFactoryInterface
{
    public static function getInstance(): AutowiringFactoryInterface;
}
