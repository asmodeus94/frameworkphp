<?php

namespace App\Helper\Traits;


trait ConstantExportTrait
{
    static function getConstants(): array
    {
        return (new \ReflectionClass(__CLASS__))->getConstants();
    }
}
