<?php

namespace App\Helper;


trait ConstantExportTrait
{
    static function getConstants(): array
    {
        return (new \ReflectionClass(__CLASS__))->getConstants();
    }
}
