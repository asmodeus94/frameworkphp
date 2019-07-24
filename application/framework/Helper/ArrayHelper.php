<?php

namespace App\Helper;


class ArrayHelper
{
    public static function areFieldsFilled(array $array, array $fields)
    {
        if (empty($array) || empty($fields)) {
            return false;
        }

        foreach ($fields as $field) {
            if (!isset($array[$field]) || empty($array[$field])) {
                return false;
            }
        }

        return true;
    }
}
